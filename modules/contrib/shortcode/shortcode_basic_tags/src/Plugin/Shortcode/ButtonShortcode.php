<?php

declare(strict_types=1);

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\MediaUrlResolverInterface;
use Drupal\shortcode\Plugin\MediaUrlResolverTrait;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * The image shortcode.
 */
#[Shortcode(
  id: 'button',
  title: new TranslatableMarkup('Button'),
  description: new TranslatableMarkup('Insert a link formatted like a button.'),
)]
class ButtonShortcode extends ShortcodeBase {

  use MediaUrlResolverTrait;

  /**
   * Constructs a ButtonShortcode plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\shortcode\MediaUrlResolverInterface $mediaUrlResolver
   *   The media URL resolver service, used to resolve `media_file_url`
   *   attribute paths that point at a media entity.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    protected MediaUrlResolverInterface $mediaUrlResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'path' => '<front>',
      'url' => '',
      'title' => '',
      'class' => '',
      'id' => '',
      'style' => '',
      'media_file_url' => FALSE,
    ],
      $attributes
    );
    $url = $attributes['url'];
    if (empty($url)) {
      $url = $this->getUrlFromPath($attributes['path'], $attributes['media_file_url']);
    }
    $title = $this->getTitleFromAttributes($attributes['title'], $text);
    $class = $this->addClass($attributes['class'], 'button');

    // Build element attributes to be used in twig.
    $element_attributes = [
      'href' => $url,
      'class' => $class,
      'id' => $attributes['id'],
      'style' => $attributes['style'],
      'title' => $title,
    ];

    // Filter away empty attributes.
    $element_attributes = array_filter($element_attributes);

    $output = [
      '#theme' => 'shortcode_button',
    // Not required for rendering, just for extra context.
      '#url' => $url,
      '#attributes' => $element_attributes,
      '#text' => $text,
    ];

    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): string {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[button path="path" (class="additional class")]text[/button]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Inserts a link formatted like as a button. The <em>path</em> parameter provides the link target (the default is the front page).
    The <em>title</em> will be formatted as a link title (small tooltip over the link - helps for SEO).
    Additional class names can be added by the <em>class</em> parameter. The media entity links converted to the direct media urls if you add the <em>media_file_url=true</em> parameter.') . '</p>';
    }
    else {
      $output[] = $this->t('Inserts a link formatted as a button. Use the url parameter for the link.') . '</p>';
    }
    return implode(' ', $output);
  }

}
