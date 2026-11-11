<?php

namespace Drupal\Tests\scanner\Functional\Media;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the search and replace functionality on media entities.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class MediaSearchTest extends ScannerTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_test_source',
    'file',
    'image',
    'link',
    'views',
    'scanner',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $entityType = 'media';

  /**
   * The media bundle used by tests.
   */
  protected string $bundleId = 'scanner_test_media';

  /**
   * The ID of the media entity created during setUp().
   */
  protected int $mediaId;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp(): void {
    parent::setUp();

    // Cheapest media source that doesn't require a real file upload.
    $this->createMediaType('test', ['id' => $this->bundleId]);

    // Add the standard scannable fields so we can search inside a media item.
    $this->addTestFields('media', $this->bundleId);

    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    $media = Media::create([
      'bundle' => $this->bundleId,
      'name' => 'Scanner test media',
      'field_text_long' => [
        [
          'value' => '<p>Wordpress is the best CMS.</p>',
          'format' => $default_format,
        ],
      ],
      'field_string' => [
        ['value' => 'Test short string'],
      ],
      'status' => 1,
    ]);
    $media->save();
    $this->mediaId = (int) $media->id();

    $this->adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($this->adminUser);
    $this->configureScannerScope(
      ["media:$this->bundleId"],
      [
        "media:$this->bundleId:field_text_long",
        "media:$this->bundleId:field_string",
      ],
    );

    $this->replaceUser = $this->createUser([
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($this->replaceUser);
  }

  /**
   * Tests replacing text in a media entity's long-text field.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testReplaceMediaTextLong(): void {
    $this->executeSearch('Wordpress');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    $this->executeReplacement('Wordpress', 'Drupal');
    $this->assertSession()->pageTextContains('entities processed.');

    $media = $this->entityStorage()->loadUnchanged($this->mediaId);
    $this->assertEquals('<p>Drupal is the best CMS.</p>', $media->get('field_text_long')->value);
    // Untouched fields stay put.
    $this->assertEquals('Test short string', $media->get('field_string')->value);

    $this->executeReplacement('short', 'tiny');
    $this->assertSession()->pageTextContains('entities processed.');

    $media = $this->entityStorage()->loadUnchanged($this->mediaId);
    $this->assertEquals('Test tiny string', $media->get('field_string')->value);
  }

}
