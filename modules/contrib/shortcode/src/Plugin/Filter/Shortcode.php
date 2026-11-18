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
use Drupal\shortcode\ShortcodePluginManager;
use Drupal\shortcode\ShortcodeService;

/**
 * Provides a filter for insert view.
 */
#[Filter(
  id: 'shortcode',
  title: new TranslatableMarkup('Shortcodes'),
  description: new TranslatableMarkup('Provides WP like shortcodes to text formats.'),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
)]
class Shortcode extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a \Drupal\shortcode\Plugin\Filter\Shortcode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\shortcode\ShortcodeService $shortCodeService
   *   The shortcode service to load shortcodes.
   * @param \Drupal\shortcode\ShortcodePluginManager $shortcodePluginManager
   *   The shortcode plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ShortcodeService $shortCodeService,
    protected ShortcodePluginManager $shortcodePluginManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $shortcodes = $this->shortCodeService->loadShortcodePlugins();

    $shortcodes_by_provider = [];

    // Group shortcodes by provider.
    foreach ($shortcodes as $shortcode_id => $shortcode_info) {
      $provider_id = $shortcode_info['provider'];
      if (!isset($shortcodes_by_provider[$provider_id])) {
        $shortcodes_by_provider[$provider_id] = [];
      }
      $shortcodes_by_provider[$provider_id][$shortcode_id] = $shortcode_info;
    }

    // Generate form elements.
    $settings = [];
    foreach ($shortcodes_by_provider as $provider_id => $shortcodes) {
      // Add section header.
      $settings['header-' . $provider_id] = [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#attributes' => [
          'class' => 'shortcodeSectionHeader',
        ],
        '#value' => $this->t('Shortcodes provided by @provider', ['@provider' => $provider_id]),
      ];

      // Sort definitions by weight property.
      $sorted_shortcodes = $shortcodes;
      uasort($sorted_shortcodes, static function ($a, $b) {
        return $b['weight'] - $a['weight'];
      });

      foreach ($sorted_shortcodes as $shortcode_id => $shortcode_info) {
        $settings[$shortcode_id] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable %name shortcode', ['%name' => $shortcode_info['title']]),
          '#default_value' => $this->settings[$shortcode_id] ?? TRUE,
          '#description' => $shortcode_info['description'] ?? $this->t('Enable or disable this shortcode in this input format'),
        ];
      }
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process($text, $langcode): FilterProcessResult {
    if (!empty($text)) {
      $text = $this->shortCodeService->process($text, $langcode, $this);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE): string {
    // Get enabled shortcodes for this text format.
    $shortcodes = $this->shortCodeService->getShortcodePlugins($this);
    // Gather tips defined in all enabled plugins.
    $tips = [];
    foreach ($shortcodes as $shortcode_info) {
      /** @var \Drupal\shortcode\Plugin\ShortcodeInterface $shortcode */
      $shortcode = $this->shortcodePluginManager->createInstance($shortcode_info['id']);
      $tips[] = $shortcode->tips($long);
    }

    $output = '';
    foreach ($tips as $tip) {
      $output .= '<li>' . $tip . '</li>';
    }
    return $this->t('<p>You can use wp-like shortcodes such as:</p>') .
      '<ul>' . $output . '</ul>';
  }

}
