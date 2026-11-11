<?php

namespace Drupal\Tests\scanner\Functional\Commerce;

use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Tests search functionality with multiple product variations of the same type.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class CommerceProductVariationSearchTest extends ScannerTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'link',
    'views',
    'commerce_product',
    'scanner',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $entityType = 'commerce_product_variation';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a Commerce Product type.
    $commerce_type_id = 'scanner_test_product_type';
    $this->createCommerceProductType(['id' => $commerce_type_id]);

    // Create a test Commerce Product Variation.
    $this->createCommerceProductVariationType(['id' => $commerce_type_id]);

    // Create a product.
    $product = Product::create([
      'type' => $commerce_type_id,
    ]);
    $product->save();

    // Create two products with the same search text.
    $variation1 = ProductVariation::create([
      'type' => $commerce_type_id,
      'field_text_long' => [
        'value' => 'The findme keyword appears here.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $variation1->save();
    $product->addVariation($variation1)->save();

    $variation2 = ProductVariation::create([
      'type' => $commerce_type_id,
      'field_text_long' => [
        'value' => 'Another product with findme in it.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $variation2->save();
    $product->addVariation($variation2)->save();

    // Configure scanner: enable product variation type and field.
    $this->adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($this->adminUser);
    $this->configureScannerScope(
      ["commerce_product_variation:$commerce_type_id"],
      ["commerce_product_variation:$commerce_type_id:field_text_long"],
    );

    // Switch to a user with search permission.
    $this->searchUser = $this->createUser([
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($this->searchUser);
  }

  /**
   * Tests that search finds matches in all products of the same type.
   */
  public function testSearchFindsAllProducts(): void {
    $this->executeSearch('findme');

    // Must find 2 matches (one per product), not 1.
    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');

    // Confirm the matched text.
    $this->assertSession()->pageTextContains('findme keyword appears');
    $this->assertSession()->pageTextContains('findme in');
  }

  /**
   * Tests that replace works on all products of the same type.
   */
  public function testReplaceAllProducts(): void {
    $this->executeReplacement('findme', 'replaced');
    $this->assertSession()->pageTextContains('2 entities processed.');
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // Verify both products were replaced.
    $this->executeSearch('findme');
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    $this->executeSearch('replaced');
    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');
  }

}
