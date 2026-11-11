<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Comment entities.
 */
#[Scanner(
  id: 'scanner_comment',
  type: 'comment',
)]
class Comment extends Entity {

}
