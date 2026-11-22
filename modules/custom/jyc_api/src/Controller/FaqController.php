<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Published "FAQ" nodes of one FAQ category, as JSON.
 *
 * GET /api/faq/1           -> FAQs of term 1 (e.g. About), English
 * GET /api/faq/1?lang=ar   -> the same in Arabic (untranslated ones stay in English)
 *
 * Sorted by field_weigth (lowest first), then by question.
 *
 * {
 *   "term": { "tid": 1, "name": "About" },
 *   "items": [
 *     { "nid": 50, "langcode": "en", "question": "…", "answer": "<p>…</p>", "weight": 10 },
 *     …
 *   ]
 * }
 *
 * 404 if the term doesn't exist or isn't in the "faq_taxonomy" vocabulary.
 */
final class FaqController extends ControllerBase {

  private const VOCABULARY = 'faq_taxonomy';

  private const MAX_ITEMS = 500;

  public function faq(string $tid, Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }

    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load((int) $tid);
    if (!$term instanceof TermInterface || $term->bundle() !== self::VOCABULARY) {
      throw new NotFoundHttpException();
    }

    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:faq'])
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions'])
      ->addCacheableDependency($term);

    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'faq')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_faq_taxonomy', $term->id())
      ->sort('field_weigth', 'ASC')
      ->sort('title', 'ASC')
      ->sort('nid', 'ASC')
      ->range(0, self::MAX_ITEMS)
      ->execute();

    $items = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      if (!$node instanceof NodeInterface || !$node->access('view')) {
        continue;
      }
      $cache->addCacheableDependency($node);
      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }
      $answer = '';
      if ($node->hasField('field_content') && !$node->get('field_content')->isEmpty()) {
        $item = $node->get('field_content')->first();
        $answer = trim((string) $item->get('processed')->getValue());
        if ($item->format) {
          $cache->addCacheTags(['config:filter.format.' . $item->format]);
        }
      }
      $items[] = [
        'nid' => (int) $node->id(),
        'langcode' => $node->language()->getId(),
        'question' => (string) $node->label(),
        'answer' => $answer,
        'weight' => (int) ($node->get('field_weigth')->value ?? 0),
      ];
    }

    if ($term->hasTranslation($langcode)) {
      $term = $term->getTranslation($langcode);
    }
    $response = new CacheableJsonResponse([
      'term' => ['tid' => (int) $term->id(), 'name' => (string) $term->label()],
      'items' => $items,
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

}
