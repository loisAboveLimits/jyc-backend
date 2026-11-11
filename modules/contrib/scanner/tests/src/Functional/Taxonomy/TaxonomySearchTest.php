<?php

namespace Drupal\Tests\scanner\Functional\Taxonomy;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the search and replace functionality on taxonomy terms.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class TaxonomySearchTest extends ScannerTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'text',
    'link',
    'views',
    'scanner',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $entityType = 'taxonomy_term';

  /**
   * User with permission to configure the scanner.
   */
  protected AccountInterface $adminUser;

  /**
   * User with permission to run search + replace.
   */
  protected AccountInterface $replaceUser;

  /**
   * The vocabulary machine name terms are created against.
   */
  protected string $vocabularyId = 'scanner_test_vocab';

  /**
   * The ID of the term created during setUp().
   */
  protected int $termId;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp(): void {
    parent::setUp();

    // createVocabulary() also attaches the standard test fields
    // (field_text_long, field_string, etc.) to the new vocabulary.
    $this->createVocabulary(['vid' => $this->vocabularyId, 'name' => $this->vocabularyId]);

    // @phpstan-ignore function.deprecated
    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    $term = $this->createTerm([
      'vid' => $this->vocabularyId,
      'name' => 'Scanner test term',
      'field_text_long' => [
        [
          'value' => '<p>The mango smoothie is ready.</p>',
          'format' => $default_format,
        ],
      ],
      'field_string' => [
        ['value' => 'Test short string'],
      ],
    ]);
    $this->termId = $term->id();

    $this->adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($this->adminUser);
    $this->configureScannerScope(
      ["taxonomy_term:$this->vocabularyId"],
      [
        "taxonomy_term:$this->vocabularyId:field_text_long",
        "taxonomy_term:$this->vocabularyId:field_string",
      ],
    );

    $this->replaceUser = $this->createUser([
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($this->replaceUser);
  }

  /**
   * Tests replacing text in a taxonomy term's long-text field.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testReplaceTermTextLong(): void {
    $this->executeSearch('mango');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    $this->executeReplacement('mango', 'strawberry');
    $this->assertSession()->pageTextContains('1 entities processed.');

    $term = $this->entityStorage()->loadUnchanged($this->termId);
    $this->assertEquals('<p>The strawberry smoothie is ready.</p>', $term->field_text_long->value);
    // Untouched fields stay put.
    $this->assertEquals('Test short string', $term->field_string->value);
  }

}
