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
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * "Blogs" posts as JSON, for the Next.js blog pages.
 *
 * GET /api/blogs?lang=ar          -> every published post (no body), newest first:
 *   [{ "nid", "slug", "langcode", "title", "summary", "date": "2026-11-10",
 *      "image": { "url", "alt", "width", "height" } | null }, …]
 *
 * GET /api/blogs/{slug}?lang=ar   -> one post, with its body and gallery:
 *   { …same fields…, "body": "<p>…</p>",
 *     "gallery": [{ "url", "alt", "width", "height" }, …] }
 *   404 if there is no published post with that slug.
 *
 * The slug is the end of the post's URL alias (/blogs/{slug}); a post
 * without such an alias uses its node id as slug. Untranslated posts are
 * returned in their original language. "summary" is the summary of
 * field_content, or the start of its text when the summary is empty.
 */
final class BlogsController extends ControllerBase {

  /** Image style used for the images (falls back to the original file). */
  private const IMAGE_STYLE = 'max_2600x2600';

  private const MAX_POSTS = 1000;

  public function __construct(private readonly AliasManagerInterface $aliasManager) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('path_alias.manager'));
  }

  public function list(Request $request): CacheableJsonResponse {
    $langcode = $this->langcode($request);
    $cache = $this->cacheMetadata();

    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'blogs')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'DESC')
      ->sort('nid', 'DESC')
      ->range(0, self::MAX_POSTS)
      ->execute();

    $posts = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      if ($node instanceof NodeInterface && $node->access('view')) {
        $posts[] = $this->postData($node, $langcode, $cache, FALSE);
      }
    }

    $response = new CacheableJsonResponse($posts);
    $response->addCacheableDependency($cache);
    return $response;
  }

  public function post(string $slug, Request $request): CacheableJsonResponse {
    $langcode = $this->langcode($request);
    $cache = $this->cacheMetadata();

    $node = $this->findBySlug($slug);
    if (!$node instanceof NodeInterface || $node->bundle() !== 'blogs' || !$node->isPublished() || !$node->access('view')) {
      throw new NotFoundHttpException();
    }

    $response = new CacheableJsonResponse($this->postData($node, $langcode, $cache, TRUE));
    $response->addCacheableDependency($cache);
    return $response;
  }

  private function langcode(Request $request): string {
    $langcode = (string) $request->query->get('lang', '');
    return isset($this->languageManager()->getLanguages()[$langcode])
      ? $langcode
      : $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  }

  private function cacheMetadata(): CacheableMetadata {
    return (new CacheableMetadata())
      ->addCacheTags(['node_list:blogs'])
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);
  }

  /** /blogs/{slug} in any language, or a node id. */
  private function findBySlug(string $slug): ?NodeInterface {
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
      return NULL;
    }
    $aliases = $this->entityTypeManager()->getStorage('path_alias')->loadByProperties(['alias' => "/blogs/$slug"]);
    foreach ($aliases as $alias) {
      if (preg_match('#^/node/(\d+)$#', $alias->getPath(), $match)) {
        $node = $this->entityTypeManager()->getStorage('node')->load($match[1]);
        if ($node instanceof NodeInterface) {
          return $node;
        }
      }
    }
    if (ctype_digit($slug)) {
      $node = $this->entityTypeManager()->getStorage('node')->load((int) $slug);
      return $node instanceof NodeInterface ? $node : NULL;
    }
    return NULL;
  }

  private function slug(NodeInterface $node): string {
    $default = $this->languageManager()->getDefaultLanguage()->getId();
    $path = '/node/' . $node->id();
    foreach ([$default, $node->getUntranslated()->language()->getId()] as $langcode) {
      $alias = $this->aliasManager->getAliasByPath($path, $langcode);
      if (preg_match('#^/blogs/([A-Za-z0-9_-]+)$#', $alias, $match)) {
        return $match[1];
      }
    }
    return (string) $node->id();
  }

  private function postData(NodeInterface $node, string $langcode, CacheableMetadata $cache, bool $full): array {
    $cache->addCacheableDependency($node);
    $slug = $this->slug($node);
    if ($node->hasTranslation($langcode)) {
      $node = $node->getTranslation($langcode);
    }

    $body = '';
    $summary = '';
    if ($node->hasField('field_content') && !$node->get('field_content')->isEmpty()) {
      $item = $node->get('field_content')->first();
      $body = trim((string) $item->get('processed')->getValue());
      $summary = trim(strip_tags((string) ($item->get('summary_processed')->getValue() ?? '')));
      if ($item->format) {
        $cache->addCacheTags(['config:filter.format.' . $item->format]);
      }
    }
    if ($summary === '') {
      $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
      $summary = mb_strlen($text) > 220 ? rtrim(mb_substr($text, 0, 217)) . '…' : $text;
    }

    $data = [
      'nid' => (int) $node->id(),
      'slug' => $slug,
      'langcode' => $node->language()->getId(),
      'title' => (string) $node->label(),
      'summary' => html_entity_decode($summary, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
      'date' => $this->dateFormatter()->format((int) $node->getCreatedTime(), 'custom', 'Y-m-d'),
      'image' => $this->image($node, 'field_main_image', $langcode, $cache)[0] ?? NULL,
    ];
    if ($full) {
      $data['body'] = $body;
      $data['gallery'] = $this->image($node, 'field_gallery', $langcode, $cache);
    }
    return $data;
  }

  /**
   * The image media of a field as [{ url, alt, width, height }, …]; other
   * media (e.g. videos in the gallery) are skipped.
   */
  private function image(NodeInterface $node, string $field, string $langcode, CacheableMetadata $cache): array {
    if (!$node->hasField($field)) {
      return [];
    }
    $style = $this->entityTypeManager()->getStorage('image_style')->load(self::IMAGE_STYLE);
    $images = [];
    foreach ($node->get($field) as $reference) {
      $media = $reference->entity;
      if (!$media instanceof MediaInterface || $media->bundle() !== 'image') {
        continue;
      }
      $cache->addCacheableDependency($media);
      if ($media->hasTranslation($langcode)) {
        $media = $media->getTranslation($langcode);
      }
      $item = $media->get('field_media_image')->first();
      $file = $item?->entity;
      if (!$file instanceof FileInterface) {
        continue;
      }
      $cache->addCacheableDependency($file);
      $images[] = [
        'url' => $style ? $style->buildUrl($file->getFileUri()) : $this->fileUrlGenerator()->generateAbsoluteString($file->getFileUri()),
        'alt' => (string) ($item->get('alt')->getValue() ?? ''),
        'width' => (int) ($item->get('width')->getValue() ?? 0),
        'height' => (int) ($item->get('height')->getValue() ?? 0),
      ];
    }
    return $images;
  }

  private function dateFormatter() {
    return \Drupal::service('date.formatter');
  }

  private function fileUrlGenerator() {
    return \Drupal::service('file_url_generator');
  }

}
