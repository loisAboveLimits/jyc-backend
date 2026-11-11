<?php

namespace Drupal\Tests\scanner\Functional;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests search and replace on commerce product attribute value entities.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class CommerceProductAttributeValueSearchTest extends ScannerTestBase {

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
   * The type of this entity.
   */
  protected string $entityType = 'commerce_product_attribute_value';

  /**
   * The attribute (bundle) machine name.
   */
  protected string $attributeId = 'color';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an attribute, which is the bundle for its values.
    $attribute = ProductAttribute::create([
      'id' => $this->attributeId,
      'label' => 'Color',
    ]);
    $attribute->save();

    // Add the standard test fields to the attribute value bundle.
    $this->addTestFields('commerce_product_attribute_value', $this->attributeId);

    // Create two attribute values sharing the same search text.
    $value1 = ProductAttributeValue::create([
      'attribute' => $this->attributeId,
      'name' => 'Red',
      'field_text_long' => [
        'value' => 'The findme keyword appears here.',
        'format' => 'plain_text',
      ],
    ]);
    $value1->save();

    $value2 = ProductAttributeValue::create([
      'attribute' => $this->attributeId,
      'name' => 'Green',
      'field_text_long' => [
        'value' => 'Another value with findme in it.',
        'format' => 'plain_text',
      ],
    ]);
    $value2->save();

    // Configure scanner: enable the attribute value bundle and field.
    $this->adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($this->adminUser);

    $this->configureScannerScope(
      ["commerce_product_attribute_value:$this->attributeId"],
      ["commerce_product_attribute_value:$this->attributeId:field_text_long"],
    );

    // Switch to a user with search permission.
    $this->searchUser = $this->createUser([
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($this->searchUser);
  }

  /**
   * Tests that search finds matches in all attribute values of a bundle.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearchFindsAllValues(): void {
    $this->executeSearch('findme');

    // Must find 2 matches (one per value), not 1.
    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');

    // Confirm the matched text.
    $this->assertSession()->pageTextContains('findme keyword appears');
    $this->assertSession()->pageTextContains('findme in');
  }

  /**
   * Tests that replace works on all attribute values of a bundle.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testReplaceAllValues(): void {
    $this->executeReplacement('findme', 'replaced');
    $this->assertSession()->pageTextContains('2 entities processed.');
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // Verify both values were replaced.
    $this->executeSearch('findme');
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    $this->executeSearch('replaced');
    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');
  }

}
