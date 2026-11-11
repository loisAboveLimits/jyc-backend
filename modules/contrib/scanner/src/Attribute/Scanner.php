<?php

namespace Drupal\scanner\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a Scanner plugin attribute object.
 *
 * @see \Drupal\scanner\Plugin\ScannerPluginInterface
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Scanner extends Plugin {

  /**
   * Constructs a Scanner attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param string $type
   *   The entity type (ex: node, user, paragraph, etc).
   */
  public function __construct(
    public readonly string $id,
    public readonly string $type,
  ) {}

}
