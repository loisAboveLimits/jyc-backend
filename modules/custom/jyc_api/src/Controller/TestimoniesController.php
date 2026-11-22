<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Published "Testimonies" nodes as JSON, for the "Visitors' Experience" slider.
 *
 * GET /api/testimonies           -> English
 * GET /api/testimonies?lang=ar   -> Arabic (untranslated ones stay in English)
 * GET /api/testimonies?limit=5   -> only the first 5
 *
 * Order: oldest first ("authored on"), so editors control the order by date.
 *
 * [
 *   { "nid": 120, "langcode": "en",
 *     "name": "…",      (title)
 *     "title": "…",     (summary of field_content: the review's headline)
 *     "text": "…",      (field_content, plain text)
 *     "rating": 5 },    (field_ratings, 1–5)
 *   …
 * ]
 */
final class TestimoniesController extends ControllerBase {

  public function testimonies(Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    $limit = max(0, min(100, (int) $request->query->get('limit', 0)));

    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:testimonies'])
      ->addCacheContexts(['url.query_args:lang', 'url.query_args:limit', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);

    $storage = $this->entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'testimonies')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'ASC')
      ->sort('nid', 'ASC')
      ->range(0, $limit ?: 100);

    $items = [];
    foreach ($storage->loadMultiple($query->execute()) as $node) {
      if (!$node instanceof NodeInterface || !$node->access('view')) {
        continue;
      }
      $cache->addCacheableDependency($node);
      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }
      $content = $node->get('field_content')->first();
      $items[] = [
        'nid' => (int) $node->id(),
        'langcode' => $node->language()->getId(),
        'name' => (string) $node->label(),
        'title' => trim((string) ($content?->summary ?? '')),
        'text' => trim((string) ($content?->value ?? '')),
        'rating' => max(0, min(5, (int) ($node->get('field_ratings')->value ?? 0))),
      ];
    }

    $response = new CacheableJsonResponse($items);
    $response->addCacheableDependency($cache);
    return $response;
  }

}
