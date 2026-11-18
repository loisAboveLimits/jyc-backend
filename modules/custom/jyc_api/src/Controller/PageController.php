<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A "Pages" node as JSON for the Next.js hero.
 *
 * GET /api/page/5          -> English
 * GET /api/page/5?lang=ar  -> Arabic
 *
 * {
 *   "nid": 5, "langcode": "en",
 *   "title": "About Us - EN",
 *   "mainTitle": "About us",          (field_main_title)
 *   "content": "<div …>…</div>",      (field_content, run through its text format)
 *   "summary": ""                     (field_content summary)
 * }
 *
 * 404 if the node doesn't exist, isn't a "pages" node, or isn't published.
 */
final class PageController extends ControllerBase {

  public function page(string $nid, Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    $node = $this->entityTypeManager()->getStorage('node')->load((int) $nid);
    if (!$node instanceof NodeInterface || $node->bundle() !== 'pages' || !$node->access('view')) {
      throw new NotFoundHttpException();
    }
    if ($node->hasTranslation($langcode)) {
      $node = $node->getTranslation($langcode);
    }

    $content = '';
    $summary = '';
    if ($node->hasField('field_content') && !$node->get('field_content')->isEmpty()) {
      $item = $node->get('field_content')->first();
      // "processed" applies the field's text format (none_ckeditor = no filters).
      $content = (string) $item->get('processed')->getValue();
      $summary = (string) ($item->get('summary_processed')->getValue() ?? '');
    }

    $cache = (new CacheableMetadata())
      ->addCacheableDependency($node)
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);
    $format = $node->hasField('field_content') ? $node->get('field_content')->format : NULL;
    if ($format) {
      $cache->addCacheTags(['config:filter.format.' . $format]);
    }

    $response = new CacheableJsonResponse([
      'nid' => (int) $node->id(),
      'langcode' => $langcode,
      'title' => (string) $node->label(),
      'mainTitle' => $node->hasField('field_main_title') ? trim((string) ($node->get('field_main_title')->value ?? '')) : '',
      'content' => trim($content),
      'summary' => trim($summary),
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

}
