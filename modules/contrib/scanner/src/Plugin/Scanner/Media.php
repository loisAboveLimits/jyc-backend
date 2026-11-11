<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Media entities.
 */
#[Scanner(
  id: 'scanner_media',
  type: 'media',
)]
class Media extends Entity {
  // No attributes or methods, just uses the base Entity class functionality.
}
