<?php

namespace Drupal\Tests\scanner\Functional\Paragraphs;

use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Tests search functionality with multiple paragraphs of the same type.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class ParagraphSearchTest extends ScannerTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'link',
    'views',
    'paragraphs',
    'scanner',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $entityType = 'paragraph';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a paragraph type with a text field.
    $paragraphs_type_id = 'scanner_test_para';
    $this->addParagraphsType($paragraphs_type_id);
    $this->addTestFields('paragraph', $paragraphs_type_id);

    // Create a node content type with a paragraphs field.
    $this->createContentType(['type' => 'scanner_para_test', 'name' => 'Scanner para test']);
    $this->addParagraphsField('scanner_para_test', 'field_paragraphs', 'node');

    // Add a nested paragraph structure.
    $this->addParagraphsField($paragraphs_type_id, 'field_paragraphs', 'paragraph');

    // Create two paragraphs with the same search text.
    $paragraph1 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_text_long' => [
        'value' => 'The findme keyword appears here.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $paragraph1->save();

    $paragraph2 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_text_long' => [
        'value' => 'Another paragraph with findme in it.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $paragraph2->save();

    // A paragraph that won't be linked to a node, so this could be found but
    // should not appear in search results as it won't have a root entity.
    $paragraph_spare = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_text_long' => [
        'value' => 'The findme keyword also appears here.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $paragraph_spare->save();

    // Paragraph 3 is nested inside paragraph 4, which is then added to the
    // node.
    $paragraph3 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_text_long' => [
        'value' => 'A nested paragraph.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $paragraph3->save();
    $paragraph4 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_paragraphs' => [
        ['target_id' => $paragraph3->id(), 'target_revision_id' => $paragraph3->getRevisionId()],
      ],
    ]);
    $paragraph4->save();

    // Paragraph 5 is nested inside paragraph 6, which is nested inside
    // paragraph 7, which is nested inside paragraph 8, which is then added to
    // the node.
    $paragraph5 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_text_long' => [
        'value' => 'A deeply nested paragraph.',
        'format' => 'plain_text',
      ],
    ] + $this->standardEntityValues());
    $paragraph5->save();
    $paragraph6 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_paragraphs' => [
        ['target_id' => $paragraph5->id(), 'target_revision_id' => $paragraph5->getRevisionId()],
      ],
    ]);
    $paragraph6->save();
    $paragraph7 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_paragraphs' => [
        ['target_id' => $paragraph6->id(), 'target_revision_id' => $paragraph6->getRevisionId()],
      ],
    ]);
    $paragraph7->save();
    $paragraph8 = Paragraph::create([
      'type' => $paragraphs_type_id,
      'field_paragraphs' => [
        ['target_id' => $paragraph7->id(), 'target_revision_id' => $paragraph7->getRevisionId()],
      ],
    ]);
    $paragraph8->save();

    // Create a node referencing both paragraphs.
    $this->createNode([
      'type' => 'scanner_para_test',
      'title' => 'Multi paragraph node',
      'field_paragraphs' => [
        ['target_id' => $paragraph1->id(), 'target_revision_id' => $paragraph1->getRevisionId()],
        ['target_id' => $paragraph2->id(), 'target_revision_id' => $paragraph2->getRevisionId()],
        ['target_id' => $paragraph4->id(), 'target_revision_id' => $paragraph4->getRevisionId()],
        ['target_id' => $paragraph8->id(), 'target_revision_id' => $paragraph8->getRevisionId()],
      ],
    ]);

    // Confirm what the node looks like.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);

    // Configure scanner: enable paragraph type and field.
    $this->adminUser = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($this->adminUser);
    $this->configureScannerScope(
      ['paragraph:scanner_test_para'],
      ['paragraph:scanner_test_para:field_text_long'],
    );

    // Switch to a user with search permission.
    $this->searchUser = $this->createUser([
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($this->searchUser);
  }

  /**
   * Tests that search finds matches in all paragraphs of the same type.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearchFindsAllParagraphs(): void {
    $this->executeSearch('findme');

    // Must find 2 matches (one per paragraph), not 1.
    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');
  }

  /**
   * Tests that search finds matches in all paragraphs of the same type.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearchNestedParagraph(): void {
    // Test the search operation.
    $this->executeSearch('nested');

    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');
  }

  /**
   * Tests that replace works on all paragraphs of the same type.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testReplaceAllParagraphs(): void {
    $this->executeReplacement('findme', 'replaced');

    // Verify both paragraphs were replaced.
    $this->executeSearch('findme');
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    $this->executeSearch('replaced');
    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');
  }

  /**
   * Tests that a replacement on paragraphs can be rolled back via the undo UI.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testUndoAfterReplace(): void {
    $session_assert = $this->assertSession();
    $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Baseline: paragraphs 1 and 2 both contain "findme".
    $this->assertStringContainsString('findme', $paragraph_storage->loadUnchanged(1)->field_text_long->value);
    $this->assertStringContainsString('findme', $paragraph_storage->loadUnchanged(2)->field_text_long->value);

    // Replace across all matching paragraphs (2 entities).
    $this->drupalGet('admin/content/scanner');
    $session_assert->statusCodeEquals(200);
    $this->submitForm(['search' => 'findme', 'replace' => 'replaced'], 'Replace');
    $this->submitForm([], 'Confirm');
    // Each matched paragraph plus every ancestor whose revision gets bumped
    // shows up in the processed-entities tally, so use the generic message
    // rather than a fixed count.
    $session_assert->pageTextContains('entities processed.');

    // Both paragraph default revisions now hold the replacement.
    $this->assertStringNotContainsString('findme', $paragraph_storage->loadUnchanged(1)->field_text_long->value);
    $this->assertStringContainsString('replaced', $paragraph_storage->loadUnchanged(1)->field_text_long->value);
    $this->assertStringNotContainsString('findme', $paragraph_storage->loadUnchanged(2)->field_text_long->value);
    $this->assertStringContainsString('replaced', $paragraph_storage->loadUnchanged(2)->field_text_long->value);

    // Roll it back from the undo listing.
    $this->drupalGet('/admin/content/scanner/undo');
    $session_assert->statusCodeEquals(200);
    $this->getSession()->getPage()->clickLink('Undo');
    $this->submitForm([], 'Confirm');
    $session_assert->pageTextContains('Undone successful for ID: 1.');

    // Each paragraph's default revision is back to its original content.
    $paragraph1_after = $paragraph_storage->loadUnchanged(1);
    $this->assertStringContainsString('findme', $paragraph1_after->field_text_long->value);
    $this->assertStringNotContainsString('replaced', $paragraph1_after->field_text_long->value);
    $paragraph2_after = $paragraph_storage->loadUnchanged(2);
    $this->assertStringContainsString('findme', $paragraph2_after->field_text_long->value);
    $this->assertStringNotContainsString('replaced', $paragraph2_after->field_text_long->value);
  }

  /**
   * Tests that a replacement in a deeply nested paragraph reaches the node.
   *
   * Paragraph 5 sits four levels below the node (node, p8, p7, p6, p5), so the
   * change must propagate up the whole chain, or it stays invisible on the
   * node.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testReplacePropagatesThroughDeepNesting(): void {
    $before = $this->collectNodeReferencedText();
    $this->assertStringContainsString('deeply', $before);
    $this->assertStringNotContainsString('shallowly', $before);

    $this->executeReplacement('deeply', 'shallowly');

    $after = $this->collectNodeReferencedText();
    $this->assertStringContainsString('shallowly', $after);
    $this->assertStringNotContainsString('deeply', $after);
  }

  /**
   * Collects the text the node's default revision references.
   *
   * Follows each reference by its target_revision_id, mirroring what renders.
   *
   * @return string
   *   The concatenated field_text_long values reachable from the node.
   */
  private function collectNodeReferencedText(): string {
    $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged(1);

    $texts = [];
    $walk = function (array $items) use (&$walk, $paragraph_storage, &$texts): void {
      foreach ($items as $item) {
        $paragraph = $paragraph_storage->loadRevision($item['target_revision_id']);
        if (!$paragraph) {
          continue;
        }
        if ($paragraph->hasField('field_text_long') && !$paragraph->get('field_text_long')->isEmpty()) {
          $texts[] = $paragraph->get('field_text_long')->value;
        }
        if ($paragraph->hasField('field_paragraphs') && !$paragraph->get('field_paragraphs')->isEmpty()) {
          $walk($paragraph->get('field_paragraphs')->getValue());
        }
      }
    };
    $walk($node->get('field_paragraphs')->getValue());

    return implode(' | ', $texts);
  }

}
