<?php

declare(strict_types=1);

namespace Drupal\shortcode;

/**
 * Resolves URLs and properties for media entities referenced by shortcodes.
 */
interface MediaUrlResolverInterface {

  /**
   * Gets the absolute file URL for a media entity's file/image field.
   *
   * @param int|string $mid
   *   The media entity ID.
   *
   * @return string|false
   *   The absolute file URL, or FALSE if the media entity or its file field
   *   could not be resolved.
   */
  public function getFileUrl(int|string $mid): string|false;

  /**
   * Gets alt text and file URI for an image media entity.
   *
   * @param int|string $mid
   *   The media entity ID.
   *
   * @return array
   *   An array with 'alt' and 'path' keys, defaulting to empty strings when
   *   the media entity, its field, or the file could not be resolved.
   */
  public function getImageProperties(int|string $mid): array;

  /**
   * Builds the derivative URL for an image style applied to a file URI.
   *
   * @param string $imageStyleId
   *   The image style machine name.
   * @param string $uri
   *   The source file URI.
   *
   * @return string|false
   *   The derivative URL, or FALSE if the image style could not be loaded.
   */
  public function getImageStyleUrl(string $imageStyleId, string $uri): string|false;

}
