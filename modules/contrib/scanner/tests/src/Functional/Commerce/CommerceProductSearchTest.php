<?php

namespace Drupal\Tests\scanner\Functional\Commerce;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\commerce_product\Entity\Product;
use Drupal\Tests\scanner\Functional\ScannerTestBase;

/**
 * Tests search functionality with multiple commerce products of the same type.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class CommerceProductSearchTest extends ScannerTestBase {

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
  protected string $entityType = 'commerce_product';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test Commerce Product.
    $commerce_type_id = 'scanner_test_product_type';
    $this->createCommerceProductType(['id' => $commerce_type_id]);

    // Create two products with the same search text.
    $product1 = Product::create([
      'type' => $commerce_type_id,
      'field_text_long' => [
        'value' => 'The findme keyword appears here.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $product1->save();

    $product2 = Product::create([
      'type' => $commerce_type_id,
      'field_text_long' => [
        'value' => 'Another product with findme in it.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $product2->save();

    // Configure scanner: enable product type and field.
    $this->adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($this->adminUser);
    $this->configureScannerScope(
      ["commerce_product:$commerce_type_id"],
      ["commerce_product:$commerce_type_id:field_text_long"],
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
