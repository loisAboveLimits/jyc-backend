<?php

namespace Drupal\scanner\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for the Scanner module.
 */
class ScannerThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'scanner_results' => [
        'variables' => [
          'title' => NULL,
          'counts' => [],
          'results' => [],
          'groups' => [],
        ],
      ],
      'scanner_results_group' => [
        'variables' => [
          'results' => [],
        ],
      ],
    ];
  }

}
