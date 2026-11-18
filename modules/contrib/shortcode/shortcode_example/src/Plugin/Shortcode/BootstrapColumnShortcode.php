<?php

declare(strict_types=1);

namespace Drupal\shortcode_example\Plugin\Shortcode;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for bootstrap columns.
 */
#[Shortcode(
  id: 'col',
  title: new TranslatableMarkup('Bootstrap column'),
  description: new TranslatableMarkup('Builds a div with bootstrap column size classes'),
)]
class BootstrapColumnShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {
    $attributes = $this->getAttributes([
      'class' => '',
      'xs' => '',
      'sm' => '',
      'md' => '',
      'lg' => '',
    ],
      $attributes
    );

    $class = $attributes['class'];
    foreach (['xs', 'sm', 'md', 'lg'] as $size) {
      if ($attributes[$size]) {
        $class = $this->addClass($class, 'col-' . $size . '-' . $attributes[$size]);
      }
    }
    return '<div class="' . $class . '">' . $text . '</div>';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): string {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[col class="custom-class" xs="12" sm="6" md="4" lg="3"]Other HTML content here [/col]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Wraps your content with a div with bootstrap column size classes. All attributes are optional but it would not be very useful unless you define at least 1 size attribute or custom all the classes yourself using class. Setting md=4 translates to the col-md-4 class, etc.') . '</p>';
    }
    else {
      $output[] = $this->t('Wraps your content with a div with bootstrap column size classes.') . '</p>';
    }

    return implode(' ', $output);
  }

}
