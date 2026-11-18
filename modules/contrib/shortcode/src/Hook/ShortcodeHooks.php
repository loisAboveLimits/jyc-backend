<?php

declare(strict_types=1);

/**
 * @file
 * Module provides ShortCodes filter framework and API (like WP ShortCodes)
 */

namespace Drupal\shortcode\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for shortcode.
 */
class ShortcodeHooks {

  /**
   * Implements hook_migration_plugins_alter().
   */
  #[Hook('migration_plugins_alter')]
  public function migrationPluginsAlter(array &$migrations) {
    if (isset($migrations['d7_filter_format'])) {
      $migration =& $migrations['d7_filter_format'];
      // Add 7.x-2.x to 2.0.x filter ID mapping.
      $migration['process']['filters']['process']['id']['map']['shortcode_text_corrector'] = 'shortcode_corrector';
    }
  }

}
