<?php

namespace Drupal\Tests\scanner\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the default admin settings functionality.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class ScannerAdminSettingsFormTest extends ScannerTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'link',
    'views',
    'commerce_product',
    'paragraphs',
    'scanner',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    // Make sure to complete the normal setup steps first.
    parent::setUp();

    // Create a test content types.
    $this->createContentTypeNode('Title test', 'Body test', 'scanner_test_node_type', 'Scanner test node type');

    // Node content type that will be ignored.
    $this->createContentTypeNode('Ignored title', 'Ignored body', 'scanner_test_ignored_node_type', 'Scanner test ignored node type');

    // Create test Paragraphs content and its fields.
    $paragraphs_type_id = 'scanner_test_paragraph_type';
    $this->addParagraphsType($paragraphs_type_id);
    $this->addTestFields('paragraph', $paragraphs_type_id);

    // Add a Paragraphs field to the node content type.
    $this->addParagraphsField('scanner_test_node_type', 'scanner_paragraphs_field', 'node');

    // Create a test Commerce Product type.
    $commerce_type_id = 'scanner_test_product_type';
    $this->createCommerceProductType(['id' => $commerce_type_id]);
    // Create a test Commerce Product Variation type.
    $this->createCommerceProductVariationType(['id' => $commerce_type_id]);

    // Create user with the appropriate admin role.
    $user = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the base admin settings form is functional.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAdminSettingsForm() {
    // Confirm the settings form loads.
    $this->drupalGet('admin/config/content/scanner');

    $session_assert = $this->assertSession();

    $session_assert->statusCodeEquals(200);

    // The defaults form emits a link back to the scanner content page.
    $session_assert->linkByHrefExists('/admin/content/scanner');

    // Check for all checkboxes including the test content type.
    $session_assert->fieldExists('scanner_mode');
    $session_assert->fieldExists('scanner_wholeword');
    $session_assert->fieldExists('scanner_regex');
    $session_assert->fieldExists('scanner_published');
    $session_assert->fieldExists('scanner_pathauto');
    $session_assert->fieldExists('scanner_language');

    // Entity-type checkboxes live on the scope tab, not the defaults form.
    $this->drupalGet('admin/config/content/scanner/entities');
    $session_assert->statusCodeEquals(200);

    // Content types.
    $session_assert->checkboxNotChecked('enabled_content_types[node:scanner_test_node_type]');
    $session_assert->checkboxNotChecked('enabled_content_types[node:scanner_test_ignored_node_type]');
    // Paragraph types.
    $session_assert->checkboxNotChecked('enabled_content_types[paragraph:scanner_test_paragraph_type]');
    // Commerce Product types.
    $session_assert->checkboxNotChecked('enabled_content_types[commerce_product:default]');
    $session_assert->checkboxNotChecked('enabled_content_types[commerce_product:scanner_test_product_type]');
    // Commerce Product Variation types.
    $session_assert->checkboxNotChecked('enabled_content_types[commerce_product_variation:default]');
    $session_assert->checkboxNotChecked('enabled_content_types[commerce_product_variation:scanner_test_product_type]');
  }

  /**
   * Tests that the supported entity fields are present.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAdminSettingsEntityTypeFields(): void {
    // Confirm the settings form loads.
    $this->drupalGet('admin/config/content/scanner/entities');

    $session_assert = $this->assertSession();

    // Enable support for a content type, paragraph and commerce entity types.
    $edit = [
      'enabled_content_types[node:scanner_test_node_type]' => 'node:scanner_test_node_type',
      'enabled_content_types[paragraph:scanner_test_paragraph_type]' => 'paragraph:scanner_test_paragraph_type',
      'enabled_content_types[commerce_product:scanner_test_product_type]' => 'commerce_product:scanner_test_product_type',
      'enabled_content_types[commerce_product_variation:scanner_test_product_type]' => 'commerce_product_variation:scanner_test_product_type',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Assert that the enabled entity type is now checked.
    $session_assert->checkboxChecked('enabled_content_types[node:scanner_test_node_type]');

    // Field checkboxes are nested under `fields_by_type[type:bundle][fields]`
    // on the scope form.
    $node_group = 'fields_by_type[node:scanner_test_node_type][fields]';
    $session_assert->fieldExists("{$node_group}[node:scanner_test_node_type:title]");
    $session_assert->fieldExists("{$node_group}[node:scanner_test_node_type:body]");

    // Paragraph text fields.
    $para_group = 'fields_by_type[paragraph:scanner_test_paragraph_type][fields]';
    $session_assert->fieldExists("{$para_group}[paragraph:scanner_test_paragraph_type:field_string]");
    $session_assert->fieldExists("{$para_group}[paragraph:scanner_test_paragraph_type:field_text]");
    $session_assert->fieldExists("{$para_group}[paragraph:scanner_test_paragraph_type:field_text_long]");
    $session_assert->fieldExists("{$para_group}[paragraph:scanner_test_paragraph_type:field_text_with_summary]");
    $session_assert->fieldExists("{$para_group}[paragraph:scanner_test_paragraph_type:field_string_long]");

    // Commerce Product text fields.
    $product_group = 'fields_by_type[commerce_product:scanner_test_product_type][fields]';
    $session_assert->fieldExists("{$product_group}[commerce_product:scanner_test_product_type:title]");
    $session_assert->fieldExists("{$product_group}[commerce_product:scanner_test_product_type:field_string]");
    $session_assert->fieldExists("{$product_group}[commerce_product:scanner_test_product_type:field_text]");
    $session_assert->fieldExists("{$product_group}[commerce_product:scanner_test_product_type:field_text_long]");
    $session_assert->fieldExists("{$product_group}[commerce_product:scanner_test_product_type:field_text_with_summary]");
    $session_assert->fieldExists("{$product_group}[commerce_product:scanner_test_product_type:field_string_long]");

    // Commerce Product Variation text fields.
    $variation_group = 'fields_by_type[commerce_product_variation:scanner_test_product_type][fields]';
    $session_assert->fieldExists("{$variation_group}[commerce_product_variation:scanner_test_product_type:title]");
    $session_assert->fieldExists("{$variation_group}[commerce_product_variation:scanner_test_product_type:field_string]");
    $session_assert->fieldExists("{$variation_group}[commerce_product_variation:scanner_test_product_type:field_text]");
    $session_assert->fieldExists("{$variation_group}[commerce_product_variation:scanner_test_product_type:field_text_long]");
    $session_assert->fieldExists("{$variation_group}[commerce_product_variation:scanner_test_product_type:field_text_with_summary]");
    $session_assert->fieldExists("{$variation_group}[commerce_product_variation:scanner_test_product_type:field_string_long]");

    // Unsupported field types are not exposed.
    $session_assert->fieldNotExists("{$para_group}[paragraph:scanner_test_paragraph_type:field_boolean]");

    // Disabled entity types' groups are not rendered at all.
    $ignored_group = 'fields_by_type[node:scanner_test_ignored_node_type][fields]';
    $session_assert->fieldNotExists("{$ignored_group}[node:scanner_test_ignored_node_type:title]");
    $session_assert->fieldNotExists("{$ignored_group}[node:scanner_test_ignored_node_type:body]");
  }

}
