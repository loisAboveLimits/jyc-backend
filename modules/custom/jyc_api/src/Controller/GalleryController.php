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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A "Galleries" node as JSON: its title and the media of field_gallery.
 *
 * GET /api/gallery/97          -> English
 * GET /api/gallery/97?lang=ar  -> Arabic title (if translated)
 *
 * {
 *   "nid": 97, "langcode": "en", "title": "Take a look inside",
 *   "items": [
 *     { "type": "image", "url": "…", "alt": "…", "width": 1000, "height": 585 },
 *     { "type": "video", "url": "…", "mime": "video/mp4" },
 *     …
 *   ]
 * }
 *
 * Items keep the order of field_gallery. 404 if the node doesn't exist,
 * isn't a "galleries" node or isn't published.
 */
final class GalleryController extends ControllerBase {

  /** Image style for the images (falls back to the original file). */
  private const IMAGE_STYLE = 'max_2600x2600';

  public function gallery(string $nid, Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }

    $node = $this->entityTypeManager()->getStorage('node')->load((int) $nid);
    if (!$node instanceof NodeInterface || $node->bundle() !== 'galleries' || !$node->isPublished() || !$node->access('view')) {
      throw new NotFoundHttpException();
    }

    $cache = (new CacheableMetadata())
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions'])
      ->addCacheableDependency($node);
    if ($node->hasTranslation($langcode)) {
      $node = $node->getTranslation($langcode);
    }

    $style = $this->entityTypeManager()->getStorage('image_style')->load(self::IMAGE_STYLE);
    $urls = \Drupal::service('file_url_generator');
    $items = [];
    foreach ($node->get('field_gallery') as $reference) {
      $media = $reference->entity;
      if (!$media instanceof MediaInterface || !$media->access('view')) {
        continue;
      }
      $cache->addCacheableDependency($media);
      if ($media->hasTranslation($langcode)) {
        $media = $media->getTranslation($langcode);
      }
      $sourceField = $media->getSource()->getConfiguration()['source_field'] ?? '';
      $item = $sourceField !== '' && $media->hasField($sourceField) ? $media->get($sourceField)->first() : NULL;
      $file = $item?->entity;
      if (!$file instanceof FileInterface) {
        continue;
      }
      $cache->addCacheableDependency($file);

      if ($media->bundle() === 'image') {
        $items[] = [
          'type' => 'image',
          'url' => $style ? $style->buildUrl($file->getFileUri()) : $urls->generateAbsoluteString($file->getFileUri()),
          'alt' => (string) ($item->get('alt')->getValue() ?? ''),
          'width' => (int) ($item->get('width')->getValue() ?? 0),
          'height' => (int) ($item->get('height')->getValue() ?? 0),
        ];
      }
      else {
        $items[] = [
          'type' => 'video',
          'url' => $urls->generateAbsoluteString($file->getFileUri()),
          'mime' => (string) $file->getMimeType(),
        ];
      }
    }

    $response = new CacheableJsonResponse([
      'nid' => (int) $node->id(),
      'langcode' => $node->language()->getId(),
      'title' => (string) $node->label(),
      'items' => $items,
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

}
