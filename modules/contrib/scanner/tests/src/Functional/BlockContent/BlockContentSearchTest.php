<?php

namespace Drupal\Tests\scanner\Functional\BlockContent;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the search and replace functionality on custom block entities.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class BlockContentSearchTest extends ScannerTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'scanner',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $entityType = 'block_content';

  /**
   * The block content bundle used by tests.
   */
  protected string $bundleId = 'scanner_test_block';

  /**
   * The ID of the block content created during setUp().
   */
  protected int $blockId;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp(): void {
    parent::setUp();

    BlockContentType::create([
      'id' => $this->bundleId,
      'label' => 'Scanner test block',
      'revision' => FALSE,
    ])->save();

    $this->addTestFields('block_content', $this->bundleId);

    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    $block = BlockContent::create([
      'type' => $this->bundleId,
      'info' => 'Scanner test block instance',
      'field_text_long' => [
        [
          'value' => '<p>Wordpress is the best CMS.</p>',
          'format' => $default_format,
        ],
      ],
      'field_string' => [
        ['value' => 'Test short string'],
      ],
    ]);
    $block->save();
    $this->blockId = (int) $block->id();

    $adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($adminUser);
    $this->configureScannerScope(
      ["block_content:$this->bundleId"],
      [
        "block_content:$this->bundleId:field_text_long",
        "block_content:$this->bundleId:field_string",
      ],
    );

    $replaceUser = $this->createUser([
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($replaceUser);
  }

  /**
   * Tests replacing text in a custom block's long-text and string fields.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testReplaceBlockFields(): void {
    $this->executeSearch('Wordpress');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    $this->executeReplacement('Wordpress', 'Drupal');
    $this->assertSession()->pageTextContains('entities processed.');

    $block = $this->entityStorage()->loadUnchanged($this->blockId);
    $this->assertEquals('<p>Drupal is the best CMS.</p>', $block->get('field_text_long')->value);
    $this->assertEquals('Test short string', $block->get('field_string')->value);

    $this->executeReplacement('short', 'tiny');
    $this->assertSession()->pageTextContains('entities processed.');

    $block = $this->entityStorage()->loadUnchanged($this->blockId);
    $this->assertEquals('Test tiny string', $block->get('field_string')->value);
  }

}
