<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling block_content entities.
 */
#[Scanner(
  id: 'scanner_block_content',
  type: 'block_content',
)]
class BlockContent extends Entity {
  // No attributes or methods, just uses the base Entity class functionality.
}
