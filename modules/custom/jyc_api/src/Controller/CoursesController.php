<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Published "Courses" nodes as JSON, for the Academy's "Find a course" cards.
 *
 * GET /api/courses           -> English
 * GET /api/courses?lang=ar   -> Arabic (untranslated ones stay in English)
 *
 * Order: oldest first ("authored on"), so editors control the order by date.
 *
 * [
 *   { "nid": 130, "langcode": "en",
 *     "title": "…",
 *     "content": "<p>…</p>",   (field_content, after its text format)
 *     "text": "…",             (the same as plain text, for the card)
 *     "bookingLink": "https://…",
 *     "image": { "url", "alt", "width", "height" } | null },
 *   …
 * ]
 */
final class CoursesController extends ControllerBase {

  private const IMAGE_STYLE = 'max_2600x2600';

  private const MAX_COURSES = 100;

  public function courses(Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }

    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:courses'])
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);

    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'courses')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'ASC')
      ->sort('nid', 'ASC')
      ->range(0, self::MAX_COURSES)
      ->execute();

    $style = $this->entityTypeManager()->getStorage('image_style')->load(self::IMAGE_STYLE);
    $courses = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      if (!$node instanceof NodeInterface || !$node->access('view')) {
        continue;
      }
      $cache->addCacheableDependency($node);
      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }

      $content = '';
      if (!$node->get('field_content')->isEmpty()) {
        $item = $node->get('field_content')->first();
        $content = trim((string) $item->get('processed')->getValue());
        if ($item->format) {
          $cache->addCacheTags(['config:filter.format.' . $item->format]);
        }
      }

      $image = NULL;
      $media = $node->get('field_main_image')->entity;
      if ($media instanceof MediaInterface) {
        $cache->addCacheableDependency($media);
        $imageItem = $media->get('field_media_image')->first();
        $file = $imageItem?->entity;
        if ($file instanceof FileInterface) {
          $cache->addCacheableDependency($file);
          $image = [
            'url' => $style ? $style->buildUrl($file->getFileUri()) : \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
            'alt' => (string) ($imageItem->get('alt')->getValue() ?? ''),
            'width' => (int) ($imageItem->get('width')->getValue() ?? 0),
            'height' => (int) ($imageItem->get('height')->getValue() ?? 0),
          ];
        }
      }

      $courses[] = [
        'nid' => (int) $node->id(),
        'langcode' => $node->language()->getId(),
        'title' => (string) $node->label(),
        'content' => $content,
        'text' => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
        'bookingLink' => trim((string) ($node->get('field_booking_link')->value ?? '')),
        'image' => $image,
      ];
    }

    $response = new CacheableJsonResponse($courses);
    $response->addCacheableDependency($cache);
    return $response;
  }

}
