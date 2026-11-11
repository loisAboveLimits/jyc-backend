<?php

/**
 * @file
 * Post-update functions for the Search and Replace Scanner module.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Set default 'word_boundaries' value to 'auto'.
 */
function scanner_post_update_set_default_word_boundaries(): ?TranslatableMarkup {
  $scanner_config = \Drupal::configFactory()->getEditable('scanner.admin_settings');
  if (!$scanner_config->get('word_boundaries')) {
    // Add the default config value if it's not set.
    $scanner_config
      ->set('word_boundaries', 'auto')->save();
    return t('Updated the default @config_name config value to %value.', [
      '@config_name' => 'word_boundaries',
      '%value' => 'auto',
    ]);
  }

  return NULL;
}
