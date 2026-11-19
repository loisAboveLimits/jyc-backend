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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A "Section" node as JSON for the Next.js <DrupalSection> component.
 *
 * GET /api/section/15          -> English
 * GET /api/section/15?lang=ar  -> Arabic (if the node is translated)
 *
 * {
 *   "nid": 15, "langcode": "en",
 *   "title": "…",
 *   "mainTitle": "…",                 (field_main_title)
 *   "content": "<section>…</section>", (field_content, run through its text format)
 *   "summary": "",
 *   "image": { "url", "alt", "width", "height" } | null   (field_main_image)
 * }
 *
 * 404 if the node doesn't exist, isn't a "section" node, or isn't published.
 */
final class SectionController extends ControllerBase {

  /** Image style used for field_main_image (falls back to the original file). */
  private const IMAGE_STYLE = 'max_2600x2600';

  public function __construct(
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('file_url_generator'));
  }

  public function section(string $nid, Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    $node = $this->entityTypeManager()->getStorage('node')->load((int) $nid);
    if (!$node instanceof NodeInterface || $node->bundle() !== 'section' || !$node->access('view')) {
      throw new NotFoundHttpException();
    }

    $cache = (new CacheableMetadata())
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);
    $response = new CacheableJsonResponse($this->sectionData($node, $langcode, $cache));
    $response->addCacheableDependency($cache);
    return $response;
  }

  /**
   * The JSON data for a section node (public so it can be tested on its own).
   */
  public function sectionData(NodeInterface $node, string $langcode, CacheableMetadata $cache): array {
    $cache->addCacheableDependency($node);
    if ($node->hasTranslation($langcode)) {
      $node = $node->getTranslation($langcode);
    }

    $content = '';
    $summary = '';
    if ($node->hasField('field_content') && !$node->get('field_content')->isEmpty()) {
      $item = $node->get('field_content')->first();
      $content = (string) $item->get('processed')->getValue();
      $summary = (string) ($item->get('summary_processed')->getValue() ?? '');
      if ($item->format) {
        $cache->addCacheTags(['config:filter.format.' . $item->format]);
      }
    }

    return [
      'nid' => (int) $node->id(),
      'langcode' => $langcode,
      'title' => (string) $node->label(),
      'mainTitle' => $node->hasField('field_main_title') ? trim((string) ($node->get('field_main_title')->value ?? '')) : '',
      'content' => trim($content),
      'summary' => trim($summary),
      'image' => $this->image($node, $langcode, $cache),
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
