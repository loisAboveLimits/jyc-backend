<?php

namespace Drupal\Tests\scanner\Functional\Comment;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the search and replace functionality on comments.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class CommentSearchTest extends ScannerTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'comment',
    'link',
    'views',
    'scanner',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $entityType = 'comment';

  /**
   * The ID of the created comment.
   */
  protected int $commentId;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp(): void {
    parent::setUp();

    // A node bundle to attach comments to.
    $content_type = 'scanner_comment_host';
    $this->createContentType([
      'type' => $content_type,
      'name' => $content_type,
    ]);

    // Default comment field ships with the 'comment' comment_type.
    $this->addDefaultCommentField('node', $content_type);

    // @phpstan-ignore function.deprecated
    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    $node = $this->createNode([
      'type' => $content_type,
      'title' => 'Host node',
    ]);

    $comment = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $node->id(),
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'subject' => 'Subject test',
      'comment_body' => [
        'value' => 'Body test',
        'format' => $default_format,
      ],
      'status' => 1,
    ]);
    $comment->save();
    $this->commentId = $comment->id();

    // Configure the scanner scope: enable comments and select the two fields.
    $this->adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($this->adminUser);
    $this->configureScannerScope(
      ['comment:comment'],
      ['comment:comment:subject', 'comment:comment:comment_body'],
    );

    $this->replaceUser = $this->createUser([
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($this->replaceUser);
  }

  /**
   * Tests replacing text in a comment body.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testReplaceCommentBody(): void {
    $this->executeSearch('Body test');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    $this->executeReplacement('Body test', 'Body scanner');
    $this->assertSession()->pageTextContains('1 entities processed.');

    $comment = $this->entityStorage()->loadUnchanged($this->commentId);
    $this->assertEquals('Body scanner', $comment->comment_body->value);
    $this->assertEquals('Subject test', $comment->subject->value);
  }

}
