<?php

declare(strict_types=1);

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Wraps content in a div with class highlight.
 */
#[Shortcode(
  id: 'highlight',
  title: new TranslatableMarkup('Highlight'),
  description: new TranslatableMarkup('Highlights text by wrapping in a span with class highlight'),
)]
class HighlightShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'class' => '',
    ],
      $attributes
    );

    $class = $this->addClass($attributes['class'], 'highlight');
    return '<span class="' . $class . '">' . $text . '</span>';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): string {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[highlight (class="additional class")]text[/highlight]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Inserts span.highlight around the text.') . '</p>';
      $output[] = '<p>' . $this->t('Sample css:') . '</p>';
      $output[] = '
      <code>
        span.highlight{
            background-color:red;
        }
        span.highlight2{
            background-color:cyan;
        }
      </code><p></p>';
    }
    else {
      $output[] = $this->t('Inserts span.highlight around the text. Additional class names can be added by the <em>class</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }

}
