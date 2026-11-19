<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * All published "Trips" nodes as JSON, for the home page slider.
 *
 * GET /api/trips          -> English
 * GET /api/trips?lang=ar  -> Arabic (untranslated trips stay in English)
 *
 * [
 *   {
 *     "nid": 21, "langcode": "en",
 *     "title": "Fire Boat",
 *     "content": "<p>…</p>",   (field_content, run through its text format)
 *     "text": "…",             (the same as plain text)
 *     "image": { "url", "alt", "width", "height" } | null,  (field_main_image)
 *     "more": "/yacht-club",   (field_more: the "Learn More" link)
 *     "rating": 4              (field_ratings, 0–5)
 *   },
 *   …
 * ]
 *
 * Order: oldest first (the order they were created in Drupal).
 */
final class TripsController extends ControllerBase {

  /** Image style used for field_main_image (falls back to the original file). */
  private const IMAGE_STYLE = 'max_2600x2600';

  public function __construct(
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('file_url_generator'));
  }

  public function trips(Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:trips'])
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);

    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'trips')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'ASC')
      ->sort('nid', 'ASC')
      ->execute();

    $trips = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      if ($node instanceof NodeInterface && $node->access('view')) {
        $trips[] = $this->tripData($node, $langcode, $cache);
      }
    }

    $response = new CacheableJsonResponse($trips);
    $response->addCacheableDependency($cache);
    return $response;
  }

  /**
   * The JSON data for one trip node.
   */
  private function tripData(NodeInterface $node, string $langcode, CacheableMetadata $cache): array {
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

    $rating = $node->hasField('field_ratings') ? (int) ($node->get('field_ratings')->value ?? 0) : 0;

    return [
      'nid' => (int) $node->id(),
      'langcode' => $node->language()->getId(),
      'title' => (string) $node->label(),
      'content' => $content,
      'text' => $text,
      'image' => $this->image($node, $langcode, $cache),
      'more' => $node->hasField('field_more') ? trim((string) ($node->get('field_more')->value ?? '')) : '',
      'rating' => max(0, min(5, $rating)),
    ];
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
