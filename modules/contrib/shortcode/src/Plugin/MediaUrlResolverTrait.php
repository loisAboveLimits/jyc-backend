<?php

declare(strict_types=1);

namespace Drupal\shortcode\Plugin;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

/**
 * Resolves a shortcode path/URL attribute, including media entity URLs.
 *
 * Include this trait only in shortcode plugins whose attributes accept a
 * path that may point at a media entity (e.g. a `media_file_url="true"`
 * attribute). The including class must inject a
 * \Drupal\shortcode\MediaUrlResolverInterface into its own
 * `protected MediaUrlResolverInterface $mediaUrlResolver` property, since
 * plugins that never resolve media URLs should not carry that dependency.
 */
trait MediaUrlResolverTrait {

  /**
   * Returns a URL to be used in a link element given path or URL.
   */
  public function getUrlFromPath($path, $media_file_url = FALSE) {
    if ($path === '<front>') {
      $path = '/';
    }

    if (UrlHelper::isValid($path, TRUE)) {
      return $path;
    }

    $path = '/' . ltrim($path, '/');

    if (!empty($media_file_url) && substr($path, 0, 6) === "/media") {
      $mid = $this->getMidFromPath($path);
      if ($mid) {
        return $this->mediaUrlResolver->getFileUrl($mid);
      }
    }

    $url = Url::fromUserInput($path, ['absolute' => TRUE]);
    return $url->toString();
  }

  /**
   * Extracts the media id from a 'media/x' system path.
   */
  public function getMidFromPath($path) {
    if (preg_match('/media\/(\d+)/', $path, $matches)) {
      return $matches[1];
    }
    return FALSE;
  }

}
