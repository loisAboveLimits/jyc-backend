<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Commerce Product Attribute Value entities.
 */
#[Scanner(
  id: 'scanner_commerce_product_attribute_value',
  type: 'commerce_product_attribute_value',
)]
class CommerceProductAttributeValue extends Entity {

  /**
   * {@inheritdoc}
   */
  public function undo(array $data): void {
    // Product attribute values are not revisionable, so this won't work.
    $this->getLogger('scanner')->error('Changes to product attribute values cannot be rolled back.');
  }

}
