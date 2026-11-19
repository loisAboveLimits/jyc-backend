<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A content block (Block layout > Content blocks) as JSON for Next.js.
 *
 * GET /api/block/2          -> English, by block id
 * GET /api/block/2?lang=ar  -> Arabic
 * GET /api/block/{uuid}     -> by the block's UUID instead of its id
 *
 * {
 *   "id": 2, "uuid": "…", "type": "basic", "langcode": "en",
 *   "info": "Footer Col2 - EN",     (block description)
 *   "body": "<div>…</div>"          (body, run through its text format)
 * }
 *
 * 404 if the block doesn't exist or isn't published.
 */
final class BlockController extends ControllerBase {

  public function block(string $id, Request $request): CacheableJsonResponse {
    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    $storage = $this->entityTypeManager()->getStorage('block_content');
    $block = ctype_digit($id)
      ? $storage->load((int) $id)
      : (current($storage->loadByProperties(['uuid' => $id])) ?: NULL);
    if (!$block instanceof BlockContentInterface || !$block->isPublished() || !$block->access('view')) {
      throw new NotFoundHttpException();
    }
    if ($block->hasTranslation($langcode)) {
      $block = $block->getTranslation($langcode);
    }

    $body = '';
    if ($block->hasField('body') && !$block->get('body')->isEmpty()) {
      $body = (string) $block->get('body')->first()->get('processed')->getValue();
    }

    $cache = (new CacheableMetadata())
      ->addCacheableDependency($block)
      ->addCacheContexts(['url.query_args:lang', 'languages:' . LanguageInterface::TYPE_CONTENT, 'user.permissions']);
    $format = $block->hasField('body') ? $block->get('body')->format : NULL;
    if ($format) {
      $cache->addCacheTags(['config:filter.format.' . $format]);
    }

    $response = new CacheableJsonResponse([
      'id' => (int) $block->id(),
      'uuid' => $block->uuid(),
      'type' => $block->bundle(),
      'langcode' => $langcode,
      'info' => (string) $block->label(),
      'body' => trim($body),
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

}
