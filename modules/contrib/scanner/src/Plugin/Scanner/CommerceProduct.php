<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Commerce Product entities.
 */
#[Scanner(
  id: 'scanner_commerce_product',
  type: 'commerce_product',
)]
class CommerceProduct extends Entity {

  /**
   * {@inheritdoc}
   */
  public function undo(array $data): void {
    // Products are not revisionable, so this won't work.
    $this->getLogger('scanner')->error('Changes to products cannot be rolled back.');
  }

}
