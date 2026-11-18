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
 * Insert div or span around the text with some css classes.
 */
#[Shortcode(
  id: 'link',
  title: new TranslatableMarkup('Link'),
  description: new TranslatableMarkup('Makes an aliased link to the given path.'),
)]
class LinkShortcode extends ShortcodeBase {

  use MediaUrlResolverTrait;

  /**
   * Constructs a LinkShortcode plugin.
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

    if ($text) {
      $title = $this->getTitleFromAttributes($attributes['title'], $text);

      // Build element attributes to be used in twig.
      $element_attributes = [
        'href' => $url,
        'class' => $attributes['class'],
        'id' => $attributes['id'],
        'style' => $attributes['style'],
        'title' => $title,
      ];

      // Filter away empty attributes.
      $element_attributes = array_filter($element_attributes);

      $output = [
        '#theme' => 'shortcode_link',
      // Not required for rendering, just for extra context.
        '#url' => $url,
        '#attributes' => $element_attributes,
        '#text' => $text,
      ];
      return $this->render($output);
    }
    // getUrlFromPath() can return FALSE for a media_file_url path pointing
    // at a deleted media entity; cast to preserve prior (implicit,
    // untyped) behavior now that process() declares a string return type.
    return (string) $url;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): string {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[link path="the drupal path" (title="link title"|class="additional class"|id="item id"|style="css style rules")]text[/link]') . '</strong>';
    if ($long) {
      $output[] = $this->t('Inserts an aliased drupal path around the text. You can omit the text and the closing [/link], you get back the url only.') . '</p>';
      $output[] = '<p>' . $this->t('Additional class names can be added by the <em>class</em> parameter. The id parameter gives the html an unique css id. In the <em>style</em> parameter you can use your own css definition.  The media entity links converted to the direct media urls if you add the <em>media_file_url=true</em> parameter.') . '</p>';
    }
    else {
      $output[] = $this->t('Inserts an aliased drupal path around the text. You can omit the text and the closing [/link], you get back the url only.') . '</p>';
    }

    return implode(' ', $output);
  }

}
