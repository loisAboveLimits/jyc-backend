<?php

namespace Drupal\Tests\image_url_formatter\FunctionalJavascript;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\File\FileExists;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\FunctionalJavascript\MediaJavascriptTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the image URL formatter.
 */
#[RunTestsInSeparateProcesses]
class ImageUrlFormatterTest extends MediaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field_ui',
    'media',
    'media_test_source',
    'image_url_formatter',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The test media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * The test image filename.
   *
   * @var string
   */
  protected $imageMediaName = 'example_1.jpeg';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install the optional configs from the standard profile.
    $extension_path = $this->container->get('extension.list.profile')->getPath('standard');
    $optional_install_path = $extension_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
    $storage = new FileStorage($optional_install_path);
    $this->container->get('config.installer')->installOptionalConfig($storage, '');
    // Reset all the static caches and list caches.
    $this->container->get('config.factory')->reset();

    // The content-link setting uses the media canonical URL.
    $this->container->get('config.factory')
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save();
    $this->container->get('router.builder')->rebuild();

    $this->file = $this->createManagedImageFile();
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => $this->imageMediaName,
      'field_media_image' => [
        'target_id' => $this->file->id(),
        'alt' => 'Image Alt Text 1',
      ],
      'status' => 1,
    ]);
    $this->media->save();
  }

  /**
   * Tests the formatter URL type settings.
   */
  public function testImageUrlFormatterUrlTypes(): void {
    $this->setFormatterSettings([
      'url_type' => 0,
      'image_style' => '',
      'image_link' => '',
    ]);
    $url = $this->renderedFormatterText();
    $this->assertStringStartsWith('http', $url);
    $this->assertStringContainsString('/sites/simpletest/', $url);
    $this->assertStringEndsWith('/files/' . $this->imageMediaName, $url);

    $this->setFormatterSettings([
      'url_type' => 1,
      'image_style' => '',
      'image_link' => '',
    ]);
    $url = $this->renderedFormatterText();
    $this->assertStringStartsWith('/', $url);
    $this->assertStringContainsString('/sites/simpletest/', $url);
    $this->assertStringEndsWith('/files/' . $this->imageMediaName, $url);
    $this->assertStringNotContainsString('://', $url);

    $this->setFormatterSettings([
      'url_type' => 2,
      'image_style' => '',
      'image_link' => '',
    ]);
    $url = $this->renderedFormatterText();
    $this->assertStringStartsNotWith('/', $url);
    $this->assertStringContainsString('sites/simpletest/', $url);
    $this->assertStringEndsWith('/files/' . $this->imageMediaName, $url);
    $this->assertStringNotContainsString('://', $url);
  }


  /**
   * Renders the formatter output as plain text.
   *
   * @return string
   *   The trimmed formatter text.
   */
  protected function renderedFormatterText(): string {
    return trim(strip_tags($this->renderMedia()));
  }

  /**
   * Tests that configured image styles are used for the emitted URL.
   */
  public function testImageUrlFormatterImageStyle(): void {
    $this->assertNotNull(ImageStyle::load('thumbnail'));

    $this->setFormatterSettings([
      'url_type' => 2,
      'image_style' => 'thumbnail',
      'image_link' => '',
    ]);

    $output = $this->renderMedia();
    $this->assertStringContainsString('styles/thumbnail/public/' . $this->imageMediaName, $output);
  }

  /**
   * Tests file and content link URL settings.
   */
  public function testImageUrlFormatterLinkSettings(): void {
    $this->setFormatterSettings([
      'url_type' => 0,
      'image_style' => '',
      'image_link' => 'file',
    ]);
    $this->assertStringContainsString($this->imageMediaName, $this->renderMedia());

    $this->setFormatterSettings([
      'url_type' => 0,
      'image_style' => '',
      'image_link' => 'content',
    ]);
    $this->assertStringContainsString('/media/' . $this->media->id(), $this->renderMedia());
  }

  /**
   * Tests that the formatter can be selected in the manage display UI.
   */
  public function testImageUrlFormatterManageDisplayUi(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/media/manage/image/display');
    $assert_session->buttonExists('Show row weights')->press();
    $assert_session->waitForElementVisible('css', '[name="fields[field_media_image][region]"]');
    $page->selectFieldOption('fields[field_media_image][region]', 'content');
    $assert_session->waitForElementVisible('css', '[name="fields[field_media_image][type]"]');
    $page->selectFieldOption('fields[field_media_image][type]', 'image_url_formatter');
    try {
      $assert_session->assertWaitOnAjaxRequest();
    }
    catch (\RuntimeException $e) {
      // Some core versions do not trigger an AJAX request for this change.
    }
    $page->pressButton('Save');

    $display = EntityViewDisplay::load('media.image.default');
    $component = $display->getComponent('field_media_image');
    $this->assertSame('image_url_formatter', $component['type']);
  }

  /**
   * Creates a managed test image file.
   *
   * @return \Drupal\file\FileInterface
   *   The created file entity.
   */
  protected function createManagedImageFile() {
    $source = $this->root . '/core/modules/media/tests/fixtures/' . $this->imageMediaName;
    $destination = 'public://' . $this->imageMediaName;
    $this->container->get('file_system')->copy($source, $destination, FileExists::Replace);

    $file = File::create([
      'uri' => $destination,
      'status' => 1,
    ]);
    $file->save();

    return $file;
  }

  /**
   * Sets formatter settings for the image media display.
   *
   * @param array $settings
   *   Formatter settings.
   */
  protected function setFormatterSettings(array $settings): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_view_display');
    $storage->resetCache(['media.image.default']);
    EntityViewDisplay::load('media.image.default')
      ->setComponent('field_media_image', [
        'type' => 'image_url_formatter',
        'label' => 'hidden',
        'settings' => $settings,
      ])
      ->save();
    $storage->resetCache(['media.image.default']);
  }

  /**
   * Renders the test media entity.
   *
   * @return string
   *   The rendered output.
   */
  protected function renderMedia(): string {
    $build = $this->container
      ->get('entity_type.manager')
      ->getViewBuilder('media')
      ->view($this->media, 'default');

    return (string) $this->container->get('renderer')->renderRoot($build);
  }

}
