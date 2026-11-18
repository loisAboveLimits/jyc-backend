<?php

declare(strict_types=1);

namespace Drupal\shortcode\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a shortcode attribute for plugin discovery.
 *
 * Plugin Namespace: Plugin\Shortcode.
 *
 * For a working example, see
 * \Drupal\shortcode_example\Plugin\Shortcode\BootstrapColumnShortcode
 *
 * @see \Drupal\shortcode\ShortcodePluginManager
 * @see \Drupal\shortcode\Plugin\ShortcodeInterface
 * @see \Drupal\shortcode\Plugin\ShortcodeBase
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Shortcode extends Plugin {

  /**
   * Constructs a Shortcode attribute.
   *
   * @param string $id
   *   The plugin ID. Used in the backend to identify shortcodes, not used
   *   for parsing.
   * @param string|null $token
   *   (optional) The token used to parse the shortcode in text. Defaults to
   *   $id if omitted.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   (optional) The human-readable name of the shortcode.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) Additional administrative information about the
   *   shortcode's behavior.
   * @param bool $status
   *   (optional) Whether this shortcode is enabled by default.
   * @param int $weight
   *   (optional) Weight of this shortcode; lower weights process first.
   * @param array $settings
   *   (optional) The default settings for the shortcode.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?string $token = NULL,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly bool $status = TRUE,
    public readonly int $weight = 99,
    public readonly array $settings = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
