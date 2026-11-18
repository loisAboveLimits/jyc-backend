<?php

declare(strict_types=1);

namespace Drupal\shortcode\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\shortcode\ShortcodeService;

/**
 * Filter that corrects HTML added by WYSIWYG editors around shortcodes.
 */
#[Filter(
  id: 'shortcode_corrector',
  title: new TranslatableMarkup('Shortcodes - HTML corrector'),
  description: new TranslatableMarkup('Corrects HTML around shortcodes. Enable only if you are using a WYSIWYG editor.'),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
)]
class ShortcodeCorrector extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a \Drupal\shortcode\Plugin\Filter\ShortcodeCorrector object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\shortcode\ShortcodeService $shortCodeService
   *   The shortcode service to load shortcodes.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ShortcodeService $shortCodeService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process($text, $langcode): FilterProcessResult {
    if (!empty($text)) {
      $text = $this->shortCodeService->postprocessText($text, $langcode, $this);
    }
    return new FilterProcessResult($text);
  }

}
