<?php

namespace Drupal\Tests\scanner\Functional\Commerce;

use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Commerce-specific helpers for scanner functional tests.
 */
trait CommerceScannerTestTrait {

  /**
   * Create a product type.
   *
   * @param array $values
   *   Items passed to the product type. If the 'id' item is not present it will
   *   be automatically generated. If the 'label' item is not present the 'id'
   *   will be used.
   *
   * @return \Drupal\commerce_product\Entity\ProductTypeInterface|\Drupal\Core\Entity\EntityInterface
   *   A fully formatted product type object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCommerceProductType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['id'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (ProductType::load($id));
    }
    else {
      $id = $values['id'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
    ];
    $product_type = ProductType::create($values);
    $status = $product_type->save();

    // Add the standard test fields.
    $this->addTestFields('commerce_product', $id);

    $this->assertEquals(SAVED_NEW, $status, (new FormattableMarkup('Created product type %type.', ['%type' => $product_type->id()]))->__toString());

    return $product_type;
  }

  /**
   * Create a product variation type.
   *
   * @param array $values
   *   Items passed to the product variation type. If the 'id' item is not
   *   present it will be automatically generated. If the 'label' item is not
   *   present the 'id' will be used.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationTypeInterface|\Drupal\Core\Entity\EntityInterface
   *   A fully formatted product variation type object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCommerceProductVariationType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['id'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (ProductVariationType::load($id));
    }
    else {
      $id = $values['id'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
    ];
    $product_variation_type = ProductVariationType::create($values);
    $status = $product_variation_type->save();

    // Add the standard test fields.
    $this->addTestFields('commerce_product_variation', $id);

    $this->assertEquals(SAVED_NEW, $status, (new FormattableMarkup('Created product variation type %type.', ['%type' => $product_variation_type->id()]))->__toString());

    return $product_variation_type;
  }

}
