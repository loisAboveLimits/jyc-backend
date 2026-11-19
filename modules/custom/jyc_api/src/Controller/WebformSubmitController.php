<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Saves a submission of a webform, for the Next.js contact forms.
 *
 * POST /api/webform/contact_jyc_marina
 * Content-Type: application/json
 * {
 *   "lang": "ar",                     (optional: language of the submission)
 *   "data": { "email": "…", "message": "…" }
 * }
 *
 * 201 { "ok": true, "sid": 12 }
 * 422 { "ok": false, "errors": { "email": "Email field is required." } }
 * 403 { "ok": false, "errors": { "form": "…closed / not allowed…" } }
 * 404 { "ok": false, "errors": { "form": "Unknown form." } }
 *
 * Only keys that are elements of the webform are saved; anything else is
 * ignored. The webform's own validation (required, email format, limits …)
 * and handlers (e.g. "send email") run as with a normal Drupal submission.
 */
final class WebformSubmitController extends ControllerBase {

  public function submit(string $webform, Request $request): JsonResponse {
    $form = $this->entityTypeManager()->getStorage('webform')->load($webform);
    if (!$form instanceof WebformInterface) {
      return $this->fail(404, ['form' => 'Unknown form.']);
    }
    if (!$form->isOpen() || !$form->access('submission_create')) {
      return $this->fail(403, ['form' => 'This form is closed or not available.']);
    }

    $body = json_decode((string) $request->getContent(), TRUE);
    if (!is_array($body) || !is_array($body['data'] ?? NULL)) {
      return $this->fail(400, ['form' => 'Send JSON: { "data": { … } }.']);
    }

    // Keep only the webform's own (input) elements, as plain strings.
    $elements = $form->getElementsInitializedFlattenedAndHasValue();
    $data = [];
    foreach ($body['data'] as $key => $value) {
      if (isset($elements[$key]) && (is_scalar($value) || $value === NULL)) {
        $data[$key] = trim((string) $value);
      }
    }

    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) ($body['lang'] ?? '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()->getDefaultLanguage()->getId();
    }

    $values = [
      'webform_id' => $form->id(),
      'langcode' => $langcode,
      'remote_addr' => $request->getClientIp() ?? '',
      'uri' => '/api/webform/' . $form->id(),
      'data' => $data,
    ];

    // Validates like the Drupal form would, then saves (and runs handlers).
    $result = WebformSubmissionForm::submitFormValues($values);
    if ($result instanceof WebformSubmissionInterface) {
      return new JsonResponse(['ok' => TRUE, 'sid' => (int) $result->id()], 201);
    }

    $errors = [];
    foreach ((array) $result as $key => $message) {
      $errors[(string) $key ?: 'form'] = trim(strip_tags((string) $message));
    }
    return $this->fail(422, $errors ?: ['form' => 'The submission could not be saved.']);
  }

  private function fail(int $status, array $errors): JsonResponse {
    return new JsonResponse(['ok' => FALSE, 'errors' => $errors], $status);
  }

}
