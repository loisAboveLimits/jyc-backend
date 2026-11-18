<?php

declare(strict_types=1);

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Insert div or span around the text with some css classes.
 */
#[Shortcode(
  id: 'random',
  title: new TranslatableMarkup('Random'),
  description: new TranslatableMarkup('Generating random text.'),
)]
class RandomShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'length' => 8,
    ],
      $attributes
    );

    $length = (int) $attributes['length'];
    $length = max($length, 8);
    $length = min($length, 99);

    // Limit to alphanumeric characters so the length is accurate when
    // rendered; chr(random_int(48, 122)) can produce '<' or '>', which get
    // parsed as markup instead of counted as text.
    $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $out = '';
    for ($i = 0; $i < $length; ++$i) {
      $out .= $charset[random_int(0, strlen($charset) - 1)];
    }

    return $out;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): string {
    $output = [];
    $output[] = '<p><strong>[random (length="8") /]</strong>';
    $output[] = $this->t('Inserts a random text with the given length.') . '</p>';
    return implode(' ', $output);
  }

}
