<?php

namespace Drupal\image_url_formatter\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the Image URL Formatter module.
 */
final class ImageUrlFormatterHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'image_url_formatter' => [
        'variables' => [
          'item' => NULL,
          'url' => NULL,
          'image_style' => NULL,
          'url_type' => NULL,
        ],
      ],
    ];
  }

}
