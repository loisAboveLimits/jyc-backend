<?php

declare(strict_types=1);

namespace Drupal\shortcode\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a base class for Shortcode plugins.
 *
 * @see \Drupal\filter\Annotation\Filter
 * @see \Drupal\shortcode\ShortcodePluginManager
 * @see \Drupal\shortcode\Plugin\ShortcodeInterface
 * @see plugin_api
 */
abstract class ShortcodeBase extends PluginBase implements ShortcodeInterface {

  /**
   * The plugin ID of this filter.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The name of the provider that owns this filter.
   *
   * @var string
   */
  public $provider;

  /**
   * A Boolean indicating whether this filter is enabled.
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * An associative array containing the configured settings of this filter.
   *
   * @var array
   */
  public $settings = [];

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RendererInterface $renderer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->provider = $this->pluginDefinition['provider'];
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'status' => $this->status,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function defaultConfiguration() {
    return [
      'provider' => $this->pluginDefinition['provider'],
      'status' => FALSE,
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->pluginDefinition['type'];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getDescription() {
    return $this->pluginDefinition['description'];
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
  public function tips($long = FALSE) {
    return '';
  }

  /**
   * Combines user attributes with known attributes.
   */
  public function getAttributes(array $defaults, array $attributes) {
    $attributes = (array) $attributes;
    $out = [];
    foreach ($defaults as $name => $default) {
      $out[$name] = array_key_exists($name, $attributes) ? $attributes[$name] : $default;
    }
    return $out;
  }

  /**
   * Add a class into a classes string if not already inside.
   */
  public function addClass($classes = '', $new_class = '') {
    $return = is_array($classes) ? $classes : explode(' ', Html::escape($classes));
    if ($new_class) {
      $return[] = Html::escape($new_class);
    }
    $return = array_unique($return);
    return implode(' ', $return);
  }

  /**
   * Returns a suitable title string given the user provided title and text.
   */
  public function getTitleFromAttributes($title, $text) {
    if ($title === '<none>') {
      $title = '';
    }
    else {
      $title = empty($title) ? trim(strip_tags($text)) : Html::escape($title);
    }
    return $title;
  }

  /**
   * Wrapper for renderPlain (renderInIsolation in Drupal 10+).
   *
   * Cast to string: renderInIsolation() returns a Markup object, but
   * ShortcodeInterface::process() implementations return plain strings.
   */
  public function render(array &$elements): string {
    return (string) $this->renderer->renderInIsolation($elements);
  }

}
