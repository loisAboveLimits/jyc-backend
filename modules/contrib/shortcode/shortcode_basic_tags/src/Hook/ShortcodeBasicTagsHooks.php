<?php

declare(strict_types=1);

/**
 * @file
 * Register hooks for ShortCodes that use Twig templates.
 */

namespace Drupal\shortcode_basic_tags\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for shortcode_basic_tags.
 */
class ShortcodeBasicTagsHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() {
    return [
          // Quote.
      'shortcode_quote' => [
        'variables' => [
          'author' => NULL,
          'class' => NULL,
          'text' => '',
        ],
      ],
          // Image.
      'shortcode_img' => [
        'variables' => [
          'src' => NULL,
          'alt' => NULL,
          'class' => '',
        ],
      ],
          // Button.
      'shortcode_button' => [
        'variables' => [
          'url' => NULL,
          'attributes' => [],
          'text' => '',
        ],
      ],
          // Dropcap.
      'shortcode_dropcap' => [
        'variables' => [
          'class' => NULL,
          'text' => '',
        ],
      ],
          // Item.
      'shortcode_item' => [
        'variables' => [
          'type' => 'div',
          'attributes' => [],
          'text' => '',
        ],
      ],
          // Clear.
      'shortcode_clear' => [
        'variables' => [
          'type' => 'div',
          'attributes' => [],
          'text' => '',
        ],
      ],
          // Link.
      'shortcode_link' => [
        'variables' => [
          'url' => NULL,
          'attributes' => [],
          'text' => '',
        ],
      ],
    ];
  }

}
