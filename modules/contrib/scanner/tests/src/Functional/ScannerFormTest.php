<?php

namespace Drupal\Tests\scanner\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ScannerForm renders correctly based on field configuration.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class ScannerFormTest extends ScannerTestBase {

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
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    $user = $this->createUser([
      'administer scanner settings',
      'perform search and replace',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests the warning message shown when no fields have been configured.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testWarningShownWhenFieldsNotConfigured(): void {
    $this->drupalGet('admin/content/scanner');
    $session = $this->assertSession();

    $session->statusCodeEquals(200);

    // The "not configured" warning should appear.
    $session->pageTextContains('The search fields were not configured.');

    // A link to the configuration page should be present.
    $session->linkExists('Search and Replace Scanner configuration');

    // The search form elements must not be rendered.
    $session->fieldNotExists('search');
    $session->fieldNotExists('replace');
    $session->buttonNotExists('Search');
    $session->buttonNotExists('Replace');
  }

  /**
   * Tests the normal form is rendered when fields have been configured.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFormRendersNormallyWhenFieldsConfigured(): void {
    $content_type = 'scanner_test_node_type';
    $this->createContentType(['type' => $content_type, 'name' => $content_type]);
    $this->addTestFields('node', $content_type);

    $this->configureScannerScope(
      ["node:$content_type"],
      ["node:$content_type:title"],
    );

    // Now visit the scanner form.
    $this->drupalGet('admin/content/scanner');
    $session = $this->assertSession();

    $session->statusCodeEquals(200);

    // The "not configured" warning must not appear.
    $session->pageTextNotContains('The search fields were not configured.');

    // The search form elements must be rendered.
    $session->fieldExists('search');
    $session->fieldExists('replace');
    $session->buttonExists('Search');
    $session->buttonExists('Replace');
  }

}
