<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Published "Events" nodes as JSON, for the "Upcoming Events" section.
 *
 * GET /api/events              -> upcoming events (not ended yet), English
 * GET /api/events?lang=ar      -> the same in Arabic (untranslated ones stay in English)
 * GET /api/events?upcoming=0   -> all events, past ones included
 * GET /api/events?limit=4      -> only the first 4
 *
 * Sorted by start date, earliest first (ASC).
 *
 * [
 *   {
 *     "nid": 27, "langcode": "en",
 *     "title": "…",
 *     "content": "<p>…</p>",           (field_content, run through its text format)
 *     "text": "…",                     (the same as plain text)
 *     "start": "2026-12-10T06:30:32Z", (field_date, UTC)
 *     "end": "2026-12-10T17:11:05Z",   (field_date end, UTC)
 *     "address": "Corniche of Jeddah", (field_address)
 *     "bookingLink": "#",              (field_booking_link)
 *     "mapLink": "#",                  (field_map_link)
 *     "image": { "url", "alt", "width", "height" } | null  (field_main_image)
 *   },
 *   …
 * ]
 */
final class EventsController extends ControllerBase {

  /** Image style used for field_main_image (falls back to the original file). */
  private const IMAGE_STYLE = 'max_2600x2600';

  public function __construct(
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly KillSwitch $pageCacheKillSwitch,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('file_url_generator'),
      $container->get('page_cache_kill_switch'),
    );
  }

  public function events(Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }
    $upcoming = $request->query->get('upcoming', '1') !== '0';
    $limit = max(0, (int) $request->query->get('limit', 0));

    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:events'])
      ->addCacheContexts([
        'url.query_args:lang',
        'url.query_args:upcoming',
        'url.query_args:limit',
        'languages:' . LanguageInterface::TYPE_CONTENT,
        'user.permissions',
      ]);

    $storage = $this->entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'events')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('field_date.value', 'ASC')
      ->sort('nid', 'ASC');

    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    if ($upcoming) {
      // Dates are stored in UTC. An event stays "upcoming" until it ends.
      $query->condition('field_date.end_value', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=');
    }
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $events = [];
    $firstEnd = NULL;
    foreach ($storage->loadMultiple($query->execute()) as $node) {
      if (!$node instanceof NodeInterface || !$node->access('view')) {
        continue;
      }
      $event = $this->eventData($node, $langcode, $cache);
      $events[] = $event;
      if ($event['end'] !== '') {
        $end = strtotime($event['end']);
        $firstEnd = $firstEnd === NULL ? $end : min($firstEnd, $end);
      }
    }

    if ($upcoming) {
      // The list changes by itself when an event ends: cache it only until then.
      $cache->setCacheMaxAge($firstEnd === NULL ? 3600 : max(60, min(3600, $firstEnd - $now->getTimestamp() + 1)));
      // The anonymous page cache ignores max-age, so keep it out of that cache.
      $this->pageCacheKillSwitch->trigger();
    }

    $response = new CacheableJsonResponse($events);
    $response->addCacheableDependency($cache);
    return $response;
  }

  /**
   * The JSON data for one event node.
   */
  private function eventData(NodeInterface $node, string $langcode, CacheableMetadata $cache): array {
    $cache->addCacheableDependency($node);
    if ($node->hasTranslation($langcode)) {
      $node = $node->getTranslation($langcode);
    }

    $content = '';
    if ($node->hasField('field_content') && !$node->get('field_content')->isEmpty()) {
      $item = $node->get('field_content')->first();
      $content = trim((string) $item->get('processed')->getValue());
      if ($item->format) {
        $cache->addCacheTags(['config:filter.format.' . $item->format]);
      }
    }
    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

    $start = '';
    $end = '';
    if ($node->hasField('field_date') && !$node->get('field_date')->isEmpty()) {
      $date = $node->get('field_date')->first();
      $start = $this->utc($date->value ?? '');
      $end = $this->utc($date->end_value ?? '') ?: $start;
    }

    return [
      'nid' => (int) $node->id(),
      'langcode' => $node->language()->getId(),
      'title' => (string) $node->label(),
      'content' => $content,
      'text' => $text,
      'start' => $start,
      'end' => $end,
      'address' => $this->stringField($node, 'field_address'),
      'bookingLink' => $this->stringField($node, 'field_booking_link'),
      'mapLink' => $this->stringField($node, 'field_map_link'),
      'image' => $this->image($node, $langcode, $cache),
    ];
  }

  /** A stored date ("2026-12-10T06:30:32", always UTC) as "2026-12-10T06:30:32Z". */
  private function utc(string $stored): string {
    return $stored === '' ? '' : $stored . 'Z';
  }

  private function stringField(NodeInterface $node, string $field): string {
    return $node->hasField($field) ? trim((string) ($node->get($field)->value ?? '')) : '';
  }

  /**
   * field_main_image (a Media image) as { url, alt, width, height }, or null.
   */
  private function image(NodeInterface $node, string $langcode, CacheableMetadata $cache): ?array {
    if (!$node->hasField('field_main_image') || $node->get('field_main_image')->isEmpty()) {
      return NULL;
    }
    $media = $node->get('field_main_image')->entity;
    if (!$media instanceof MediaInterface) {
      return NULL;
    }
    $cache->addCacheableDependency($media);
    if ($media->hasTranslation($langcode)) {
      $media = $media->getTranslation($langcode);
    }
    $sourceField = $media->getSource()->getConfiguration()['source_field'] ?? '';
    $file = $this->entityTypeManager()->getStorage('file')->load($media->getSource()->getSourceFieldValue($media));
    if (!$file instanceof FileInterface) {
      return NULL;
    }
    $cache->addCacheableDependency($file);
    $item = $media->get($sourceField)->first();
    $style = $this->entityTypeManager()->getStorage('image_style')->load(self::IMAGE_STYLE);
    return [
      'url' => $style ? $style->buildUrl($file->getFileUri()) : $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
      'alt' => (string) ($item?->get('alt')->getValue() ?? ''),
      'width' => (int) ($item?->get('width')->getValue() ?? 0),
      'height' => (int) ($item?->get('height')->getValue() ?? 0),
    ];
  }

}
