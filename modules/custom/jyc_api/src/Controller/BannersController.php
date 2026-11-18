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
 * Banners (content type "banners") for a page, as JSON for the Next.js hero.
 *
 * GET /api/banners/1          -> English banners whose field_reference is node 1
 * GET /api/banners/1?lang=ar  -> the same banners, Arabic texts
 *
 * Only published banners are returned, oldest first (the order they were
 * created in). Media URLs are absolute.
 */
final class BannersController extends ControllerBase {

  /** Image style used for photo backgrounds (falls back to the original file). */
  private const IMAGE_STYLE = 'max_2600x2600';

  public function __construct(
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('file_url_generator'));
  }

  public function banners(string $nid, Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:banners', 'node:' . $nid])
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);

    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'banners')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_reference', (int) $nid)
      ->sort('created', 'ASC')
      ->sort('nid', 'ASC')
      ->execute();

    $items = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      assert($node instanceof NodeInterface);
      $cache->addCacheableDependency($node);
      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }

      $items[] = [
        'id' => (int) $node->id(),
        'subTitleTop' => $this->text($node, 'field_sub_title_top'),
        'mainTitle' => $this->text($node, 'field_main_title'),
        'subTitleBottom' => $this->text($node, 'field_sub_title_bottom'),
        'caption' => $this->text($node, 'field_caption'),
        'media' => $this->media($node, $langcode, $cache),
      ];
    }

    $response = new CacheableJsonResponse([
      'nid' => (int) $nid,
      'langcode' => $langcode,
      'items' => $items,
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

  private function text(NodeInterface $node, string $field): string {
    return $node->hasField($field) ? trim((string) ($node->get($field)->value ?? '')) : '';
  }

  /**
   * The background from field_banner.
   *
   * @return array{type: string, url: string, alt?: string, width?: int, height?: int, mime?: string, thumbnail?: string}|null
   */
  private function media(NodeInterface $node, string $langcode, CacheableMetadata $cache): ?array {
    if (!$node->hasField('field_banner') || $node->get('field_banner')->isEmpty()) {
      return NULL;
    }
    $media = $node->get('field_banner')->entity;
    if (!$media instanceof MediaInterface) {
      return NULL;
    }
    $cache->addCacheableDependency($media);
    if ($media->hasTranslation($langcode)) {
      $media = $media->getTranslation($langcode);
    }

    $source = $media->getSource();
    $sourceField = $source->getConfiguration()['source_field'] ?? '';
    $value = $source->getSourceFieldValue($media);

    switch ($media->bundle()) {
      case 'image':
        $file = $this->entityTypeManager()->getStorage('file')->load($value);
        if (!$file instanceof FileInterface) {
          return NULL;
        }
        $cache->addCacheableDependency($file);
        $item = $media->get($sourceField)->first();
        $style = $this->entityTypeManager()->getStorage('image_style')->load(self::IMAGE_STYLE);
        if ($style) {
          $cache->addCacheableDependency($style);
        }
        return [
          'type' => 'image',
          'url' => $style ? $style->buildUrl($file->getFileUri()) : $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
          'alt' => (string) ($item?->get('alt')->getValue() ?? ''),
          'width' => (int) ($item?->get('width')->getValue() ?? 0),
          'height' => (int) ($item?->get('height')->getValue() ?? 0),
        ];

      case 'video':
        $file = $this->entityTypeManager()->getStorage('file')->load($value);
        if (!$file instanceof FileInterface) {
          return NULL;
        }
        $cache->addCacheableDependency($file);
        return [
          'type' => 'video',
          'url' => $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
          'mime' => (string) $file->getMimeType(),
        ];

      case 'remote_video':
        // YouTube/Vimeo can't play as a muted background loop, so the
        // frontend shows the video's thumbnail as a photo instead.
        $thumb = $media->get('thumbnail')->entity;
        return [
          'type' => 'remote_video',
          'url' => (string) $value,
          'thumbnail' => $thumb instanceof FileInterface ? $this->fileUrlGenerator->generateAbsoluteString($thumb->getFileUri()) : '',
          'alt' => (string) $media->label(),
        ];
    }
    return NULL;
  }

}
