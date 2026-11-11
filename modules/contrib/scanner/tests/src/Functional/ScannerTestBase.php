<?php

namespace Drupal\Tests\scanner\Functional;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\scanner\Functional\Commerce\CommerceScannerTestTrait;
use Drupal\Tests\scanner\Functional\Node\NodeScannerTestTrait;
use Drupal\Tests\scanner\Functional\Taxonomy\TaxonomyScannerTestTrait;
use Drupal\user\Entity\User;

/**
 * Helper test class with some added functions for testing.
 */
abstract class ScannerTestBase extends BrowserTestBase {

  use CommerceScannerTestTrait;
  use NodeScannerTestTrait;
  use ParagraphsTestBaseTrait;
  use TaxonomyScannerTestTrait;

  /**
   * The type of this entity.
   */
  protected string $entityType = '';

  /**
   * Admin user.
   */
  protected AccountInterface $adminUser;

  /**
   * Search user.
   */
  protected AccountInterface $searchUser;

  /**
   * User with permission to run search + replace.
   */
  protected AccountInterface $replaceUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'link',
    'views',
    'scanner',
  ];

  /**
   * Log in as user 1.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function loginUser1(): void {
    // Load user 1.
    /** @var \Drupal\user\Entity\User $account */
    $account = User::load(1);

    // Reset the password.
    $password = 'foo';
    $account->setPassword($password)->save();

    // Support old and new tests.
    $account->passRaw = $password;
    $account->pass_raw = $password;

    // Login.
    $this->drupalLogin($account);
  }

  /**
   * Returns the storage for $this->entityType.
   */
  protected function entityStorage(): ContentEntityStorageInterface {
    return $this->container->get('entity_type.manager')->getStorage($this->entityType);
  }

  /**
   * Adds a field to a given entity bundle.
   *
   * @param string $entity_type
   *   The entity type to use.
   * @param string $bundle
   *   Entity bundle to be used.
   * @param string $field_name
   *   Field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $storage_settings
   *   Settings for the field storage.
   * @param int $cardinality
   *   The cardinality for this field. Defaults to -1, allowing unlimited items.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addFieldToEntityType(string $entity_type, string $bundle, string $field_name, string $field_type, array $storage_settings = [], $cardinality = -1): void {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => $field_type,
        'cardinality' => $cardinality,
        'settings' => $storage_settings,
      ]);
      $field_storage->save();
    }
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'settings' => [
        'title' => DRUPAL_DISABLED,
      ],
    ]);
    $field->save();

    $field_type_definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_type);

    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay($entity_type, $bundle);
    $form_display->setComponent($field_name, ['type' => $field_type_definition['default_widget']])
      ->save();

    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay($entity_type, $bundle);
    $view_display->setComponent($field_name, ['type' => $field_type_definition['default_formatter']]);
    $view_display->save();
  }

  /**
   * Add fields of each supported type for testing purposes.
   *
   * @param string $entity_type
   *   The entity type to use.
   * @param string $bundle
   *   Entity bundle to be used.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addTestFields(string $entity_type, string $bundle): void {
    // Supported fields.
    $this->addFieldToEntityType($entity_type, $bundle, 'field_link', 'link');
    $this->addFieldToEntityType($entity_type, $bundle, 'field_string', 'string');
    $this->addFieldToEntityType($entity_type, $bundle, 'field_text', 'text');
    $this->addFieldToEntityType($entity_type, $bundle, 'field_text_long', 'text_long');
    $this->addFieldToEntityType($entity_type, $bundle, 'field_text_with_summary', 'text_with_summary');
    $this->addFieldToEntityType($entity_type, $bundle, 'field_string_long', 'string_long');

    // Unsupported fields.
    $this->addFieldToEntityType($entity_type, $bundle, 'field_boolean', 'boolean');
  }

  /**
   * A list of standard field values to use in entity tests.
   */
  protected function standardEntityValues(): array {
    // @phpstan-ignore function.deprecated
    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    return [
      'field_link' => [
        [
          'uri' => 'https://example.com',
          'title' => 'Link to example dot com',
        ],
        [
          'uri' => 'https://anotherexample.com',
          'title' => 'Link to anotherexample dot com',
        ],
        [
          'uri' => 'https://examplenot.com',
          'title' => 'Link to examplenot dot com',
        ],
      ],
      'field_string' => [
        ['value' => 'Test short string #1'],
        ['value' => 'Test short string #2'],
        ['value' => 'Test short string #3'],
      ],
      'field_string_long' => [
        ['value' => 'Test long string #1'],
        ['value' => 'Test long string #2'],
        ['value' => 'Test long string #3'],
      ],
      'field_text' => [
        [
          'value' => 'Text test #1',
          'format' => $default_format,
        ],
        [
          'value' => 'Text test #2',
          'format' => $default_format,
        ],
        [
          'value' => 'Text test #3',
          'format' => $default_format,
        ],
      ],
      'field_text_long' => [
        [
          'value' => '<p>Mango smoothie</p>',
          'format' => $default_format,
        ],
        [
          'value' => '<p>Banana split</p>',
          'format' => $default_format,
        ],
        [
          'value' => '<p>Egg custard</p>',
          'format' => $default_format,
        ],
      ],
      'field_text_with_summary' => [
        [
          'value' => 'Text with summary test #1',
          'format' => $default_format,
          'summary' => 'Summary test #1',
        ],
        [
          'value' => 'Text with summary test #2',
          'format' => $default_format,
          'summary' => 'Summary test #2',
        ],
        [
          'value' => 'Text with summary test #3',
          'format' => $default_format,
          'summary' => 'Summary test #3',
        ],
      ],
    ];
  }

  /**
   * Configure the scanner's entity-type + field scope via the admin UI.
   *
   * Visits the settings form and performs the two-step submit it requires:
   * enable the entity-type(s) first, then tick their fields (which only
   * render after the type is enabled and the form is rebuilt).
   *
   * @param string[] $enabled_types
   *   Keys in the shape "entity_type:bundle" to enable.
   * @param string[] $fields
   *   Keys in the shape "entity_type:bundle:field_name" to tick. Optional.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function configureScannerScope(array $enabled_types, array $fields = []): void {
    $this->drupalGet('admin/config/content/scanner/entities');
    $this->assertSession()->statusCodeEquals(200);

    $enable_edit = [];
    foreach ($enabled_types as $type_key) {
      $enable_edit["enabled_content_types[$type_key]"] = $type_key;
    }
    $this->submitForm($enable_edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);

    if (empty($fields)) {
      return;
    }

    $field_edit = [];
    foreach ($fields as $field_key) {
      // Field key is "entity_type:bundle:field_name"; the entity-scope form
      // nests each field under its type group as
      // fields_by_type[entity_type:bundle][fields][entity_type:bundle:field_name].
      [$entity_type, $bundle] = explode(':', $field_key, 3);
      $type_group = "$entity_type:$bundle";
      $field_edit["fields_by_type[$type_group][fields][$field_key]"] = $field_key;
    }
    $this->submitForm($field_edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Trigger a search.
   *
   * @param string $search
   *   The string to search for.
   * @param array $options
   *   An optional list of values to pass to the search form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function executeSearch(string $search, array $options = []): void {
    $this->drupalGet('admin/content/scanner');

    // Test the search operation.
    $this->submitForm(['search' => $search] + $options, 'Search');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure no errors were reported.
    $this->assertSession()->pageTextNotContains('An error has occurred.');
  }

  /**
   * Trigger a search & replacement.
   *
   * @param string $search
   *   The string to search for.
   * @param string $replace
   *   The string that will replace the search string.
   * @param array $options
   *   An optional list of values to pass to the form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function executeReplacement(string $search, string $replace, array $options = []): void {
    $this->drupalGet('admin/content/scanner');
    $this->assertSession()->statusCodeEquals(200);

    // Run the replacement operation.
    $this->submitForm(['search' => $search, 'replace' => $replace] + $options, 'Replace');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure no errors were reported.
    $this->assertSession()->pageTextNotContains('An error has occurred.');

    // Confirm the operation.
    $this->submitForm([], 'Confirm');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure no errors were reported.
    $this->assertSession()->pageTextNotContains('An error has occurred.');

    // Confirm something happened.
    $this->assertSession()->pageTextContains('entities processed.');
  }

}
