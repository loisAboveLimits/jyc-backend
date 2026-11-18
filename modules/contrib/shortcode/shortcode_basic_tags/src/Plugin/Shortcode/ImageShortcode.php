<?php

declare(strict_types=1);

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\MediaUrlResolverInterface;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * The image shortcode.
 */
#[Shortcode(
  id: 'img',
  title: new TranslatableMarkup('Image'),
  description: new TranslatableMarkup('Show an image.'),
)]
class ImageShortcode extends ShortcodeBase {

  /**
   * Constructs an ImageShortcode plugin.
   *
   * Handles image shortcode processing with support for media entities,
   * image styles, and direct URL sources.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service for processing render arrays.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service for creating absolute URLs from file URIs.
   * @param \Drupal\shortcode\MediaUrlResolverInterface $mediaUrlResolver
   *   The media URL resolver service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected MediaUrlResolverInterface $mediaUrlResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {
    $attributes = $this->getAttributes([
      'class' => '',
      'alt' => '',
      'src' => '',
      'mid' => '',
      'imagestyle' => '',
    ], $attributes);

    $class = $this->addClass($attributes['class'], 'img');

    if ($attributes['mid']) {
      $properties = $this->mediaUrlResolver->getImageProperties($attributes['mid']);
      if (!empty($properties['path'])) {
        if (!empty($attributes['imagestyle'])) {
          $style_url = $this->mediaUrlResolver->getImageStyleUrl($attributes['imagestyle'], $properties['path']);
          if ($style_url !== FALSE) {
            $attributes['src'] = $style_url;
          }
        }
        else {
          $attributes['src'] = $this->fileUrlGenerator->generateAbsoluteString($properties['path']);
        }
      }
      if (!empty($properties['alt']) && empty($attributes['alt'])) {
        $attributes['alt'] = $properties['alt'];
      }
    }

    $output = [
      '#theme' => 'shortcode_img',
      '#src' => $attributes['src'],
      '#class' => $class,
      '#alt' => $attributes['alt'],
    ];

    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): ?string {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[img (src="image.jpg"|mid="1") (class="additional class"|alt="alt text"|imagestyle="medium")/]') . '</strong> ';
    $output[] = $this->t('Inserts an image based on the given image URL or media ID. If a media ID is supplied with no alt text, the alt text from the media object will be applied.') . '</p>';
    return implode(' ', $output);
  }

}
