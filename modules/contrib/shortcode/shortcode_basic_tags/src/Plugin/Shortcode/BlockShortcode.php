<?php

declare(strict_types=1);

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Insert a custom block to the text.
 */
#[Shortcode(
  id: 'block',
  title: new TranslatableMarkup('Block'),
  description: new TranslatableMarkup('Insert a block.'),
)]
class BlockShortcode extends ShortcodeBase {

  /**
   * Constructs a BlockShortcode plugin for rendering block content entities.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service for converting render arrays to markup.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager for loading block content entities.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
  }

  /**
   * Processes the shortcode to render a block content entity.
   *
   * @param array $attributes
   *   Shortcode attributes containing 'id' and optional 'view' parameters.
   * @param string $text
   *   The text content within the shortcode tags.
   * @param string $langcode
   *   The language code for rendering.
   *
   * @return string
   *   The rendered block content or empty string if block not found.
   */
  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {
    $attributes = $this->getAttributes([
      'id' => '',
      'view' => 'full',
    ], $attributes);

    $blockId = (int) $attributes['id'];
    if ($blockId === 0) {
      return '';
    }

    try {
      $blockStorage = $this->entityTypeManager->getStorage('block_content');
      $blockEntity = $blockStorage->load($blockId);

      if ($blockEntity === NULL || !$blockEntity->access('view')) {
        return '';
      }

      $viewBuilder = $this->entityTypeManager->getViewBuilder('block_content');
      $renderArray = $viewBuilder->view($blockEntity, $attributes['view'], $langcode);

      return $this->render($renderArray);
    }
    catch (\Exception $e) {
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): string {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[block id="1" (view="full") /]') . '</strong>';
    $output[] = $this->t('Inserts a block.') . '</p>';

    if ($long) {
      $output[] = '<p>' . $this->t('The block display view can be specified using the <em>view</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }

}
