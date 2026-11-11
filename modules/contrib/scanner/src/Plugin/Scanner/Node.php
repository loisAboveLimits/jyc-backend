<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Node entities.
 */
#[Scanner(
  id: 'scanner_node',
  type: 'node',
)]
class Node extends Entity {

}
