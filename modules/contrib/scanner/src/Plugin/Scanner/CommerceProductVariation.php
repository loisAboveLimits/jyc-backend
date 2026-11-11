<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Commerce Product Variation entities.
 */
#[Scanner(
  id: 'scanner_commerce_product_variation',
  type: 'commerce_product_variation',
)]
class CommerceProductVariation extends Entity {

  /**
   * {@inheritdoc}
   */
  public function undo(array $data): void {
    // Products are not revisionable, so this won't work.
    $this->getLogger('scanner')->error('Changes to product variations cannot be rolled back.');
  }

}
