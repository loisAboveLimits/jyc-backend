<?php

namespace Drupal\Tests\scanner\Functional\Node;

use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\filter\FilterFormatRepositoryInterface;

/**
 * Tests the search and replace functionality on nodes.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class NodeSearchTest extends ScannerTestBase {

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
   */
  protected string $entityType = 'node';

  /**
   * {@inheritdoc}
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    // Make sure to complete the normal setup steps first.
    parent::setUp();

    $this->setUpNodeScanner();
  }

  /**
   * Test the complete search & replace operation on the body field.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testSearchReplaceBody(): void {
    $this->searchAndReplace('Body test', 'scanner');
  }

  /**
   * Tests that a replacement can be rolled back via the undo listing.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testUndo(): void {
    $session_assert = $this->assertSession();

    // Baseline: Node 1's body renders "Body test".
    $this->drupalGet('node/1');
    $session_assert->pageTextContains('Body test');

    // Replace "Body test" -> "scanner" on the one entity that matches.
    $this->drupalGet('admin/content/scanner');
    $session_assert->statusCodeEquals(200);
    $this->submitForm(['search' => 'Body test', 'replace' => 'scanner'], 'Replace');
    $this->submitForm([], 'Confirm');
    $session_assert->pageTextContains('1 entities processed.');

    // The replacement stuck on the node.
    $this->drupalGet('node/1');
    $session_assert->pageTextNotContains('Body test');
    $session_assert->pageTextContains('scanner');

    // Undo the replacement from the undo listing.
    $this->drupalGet('/admin/content/scanner/undo');
    $this->assertSession()->elementTextEquals('xpath', '//table//tbody/tr/td[4]', '1');
    $this->getSession()->getPage()->clickLink('Undo');
    $this->submitForm([], 'Confirm');
    $session_assert->pageTextContains('Undone successful for ID: 1.');

    // Node 1's body is back to the original value.
    $this->drupalGet('node/1');
    $session_assert->pageTextContains('Body test');
  }

  /**
   * Test the complete search & replace operation on the body summary field.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearchReplaceBodySummary(): void {
    $this->executeSearch('Summary test #1');

    // Confirm the expected response.
    $this->assertSession()->pageTextContains('Found 2 matches in 2 entities.');

    // Replace the string.
    $this->executeReplacement('Summary test #1', 'scanner');

    // Confirm the expected response.
    $this->assertSession()->pageTextContains('2 entities processed.');
  }

  /**
   * Test the complete search & replace operation short strings.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testSearchReplaceString(): void {
    // Perform a string replacement.
    $this->searchAndReplace('short string #2', 'shorter string #2');

    // Confirm the node value.
    $entity_before = $this->entityStorage()->load(1);
    $value_before = $entity_before->field_string->getValue();
    $expected = [
      ['value' => 'Test short string #1'],
      ['value' => 'Test shorter string #2'],
      ['value' => 'Test short string #3'],
    ];
    $this->assertEquals($value_before, $expected);

    // Perform another string replacement.
    $this->searchAndReplace('short string #3', 'shorterer string #3', FALSE);

    // Confirm the node value.
    $entity_before = $this->entityStorage()->load(1);
    $value_before = $entity_before->field_string->getValue();
    $expected = [
      ['value' => 'Test short string #1'],
      ['value' => 'Test shorter string #2'],
      ['value' => 'Test shorterer string #3'],
    ];
    $this->assertEquals($value_before, $expected);

    // Perform another string replacement.
    $this->searchAndReplace('shorterer string #3', 'shortest string #3', FALSE);

    // Confirm the node value.
    $entity_before = $this->entityStorage()->load(1);
    $value_before = $entity_before->field_string->getValue();
    $expected = [
      ['value' => 'Test short string #1'],
      ['value' => 'Test shorter string #2'],
      ['value' => 'Test shortest string #3'],
    ];
    $this->assertEquals($value_before, $expected);
  }

  /**
   * Test the complete search & replace operation on long text fields.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testSearchReplaceTextLong(): void {
    // @phpstan-ignore function.deprecated
    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    // Confirm the data prior to running the conversion.
    $entity_before = $this->entityStorage()->load(1);
    /** @var \Drupal\text\Plugin\Field\FieldType\TextFieldItemList $field_before */
    $field_before = $entity_before->field_text_long;
    $value_before = $field_before->getValue();
    $expected = [
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
    ];
    $this->assertEquals($value_before, $expected);

    // Perform the search/replacement.
    $this->searchAndReplace('Mango', 'Strawberry');

    // Validate the data changed correctly.
    $entity_after = $this->entityStorage()->load(1);
    /** @var \Drupal\text\Plugin\Field\FieldType\TextFieldItemList $field_after */
    $field_after = $entity_after->field_text_long;
    $value_after = $field_after->getValue();
    $this->assertNotEquals($value_before, $value_after);
    $expected = [
      [
        'value' => '<p>Strawberry smoothie</p>',
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
    ];
    $this->assertEquals($value_after, $expected);

    // Perform the search/replacement.
    $this->searchAndReplace('Banana', 'Pineapple', FALSE);

    // Validate the data changed correctly.
    $entity_after = $this->entityStorage()->load(1);
    /** @var \Drupal\text\Plugin\Field\FieldType\TextFieldItemList $field_after */
    $field_after = $entity_after->field_text_long;
    $value_after = $field_after->getValue();
    $this->assertNotEquals($value_before, $value_after);
    $expected = [
      [
        'value' => '<p>Strawberry smoothie</p>',
        'format' => $default_format,
      ],
      [
        'value' => '<p>Pineapple split</p>',
        'format' => $default_format,
      ],
      [
        'value' => '<p>Egg custard</p>',
        'format' => $default_format,
      ],
    ];
    $this->assertEquals($value_after, $expected);

    // Perform the search/replacement.
    $this->searchAndReplace('Egg', 'Runny', FALSE);

    // Validate the data changed correctly.
    $entity_after = $this->entityStorage()->load(1);
    /** @var \Drupal\text\Plugin\Field\FieldType\TextFieldItemList $field_after */
    $field_after = $entity_after->field_text_long;
    $value_after = $field_after->getValue();
    $this->assertNotEquals($value_before, $value_after);
    $expected = [
      [
        'value' => '<p>Strawberry smoothie</p>',
        'format' => $default_format,
      ],
      [
        'value' => '<p>Pineapple split</p>',
        'format' => $default_format,
      ],
      [
        'value' => '<p>Runny custard</p>',
        'format' => $default_format,
      ],
    ];
    $this->assertEquals($value_after, $expected);
  }

  /**
   * Test the complete search & replace operation for link fields.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testSearchReplaceLink(): void {
    $this->searchAndReplace('https://example.com', 'https://exampleNew.com');
  }

  /**
   * Test the complete search & replace operation with 'wholeword' disabled.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearchReplaceTextWholewordDisabled(): void {
    $search = 'egg/custard';

    // Look for the string without the preceding slash, first with the
    // 'wholeword' option enabled, which is the default.
    $this->executeSearch($search);
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    // Look for the string without the preceding slash with the 'wholeword'
    // option disabled; it won't make any difference to the result.
    $this->executeSearch($search, ['wholeword' => FALSE]);
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    $search = '/egg/custard';
    $replace = '/egg-custard';

    // Look for the search string with the preceding slash. Doing this search
    // with the 'wholeword' option enabled (default) won't find any results.
    $this->executeSearch($search);
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    // Perform the same search with the wholeword option disabled, which will
    // find the page.
    $this->executeSearch($search, ['wholeword' => FALSE]);
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    // Perform the search with the 'wholeword' option disabled.
    $this->executeReplacement($search, $replace, ['wholeword' => FALSE]);

    // Verify the search string is nowhere to be found.
    $this->executeSearch($search, ['wholeword' => FALSE]);
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    // Verify the replacement string can now be found.
    $this->executeSearch($replace, ['wholeword' => FALSE]);
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');
  }

  /**
   * Tests search with no matches across all fields produces no errors.
   *
   * Exercises the empty check in ScannerForm::batchSearch() where
   * $data['field'] may not be set when there are no matches.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testSearchNoMatches(): void {
    $this->drupalGet('admin/content/scanner');
    $this->assertSession()->statusCodeEquals(200);

    // Search for text that doesn't exist in any field.
    $this->submitForm(['search' => 'completelynonexistenttext'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('An error has occurred.');
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');
  }

  /**
   * Tests search matching body but not link field produces no errors.
   *
   * When both body and field_link are configured for scanning, searching for
   * text that only matches the body field should not produce errors when the
   * link field has no matches.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testSearchMatchesBodyNotLink(): void {
    $this->drupalGet('admin/content/scanner');
    $this->assertSession()->statusCodeEquals(200);

    // "Body test" exists in the body but not in the link field.
    $this->submitForm(['search' => 'Body test'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('An error has occurred.');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');
  }

  /**
   * Tests case-sensitive search and replace.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCaseSensitiveSearch(): void {
    $this->executeSearch('mango');
    $this->assertSession()->pageTextContains('Found 2 matches in 1 entities.');

    // 2. Case-insensitive: 1 hit on Node 1 ("Mango") + 2 hits on Node 2.
    $this->executeSearch('mango', ['mode' => FALSE]);
    $this->assertSession()->pageTextContains('Found 3 matches in 2 entities.');

    // 3. "Mango" (capital) case-sensitive: only Node 1, once.
    $this->executeSearch('Mango', ['mode' => TRUE]);
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    // 4. Replace "mango" case-sensitive with "replacement": only Node 2
    // has lowercase "mango" so only Node 2 gets touched.
    $this->executeReplacement('mango', 'replacement', ['mode' => TRUE]);
    $this->assertSession()->pageTextContains('1 entities processed.');

    // Verify Node 1's Mango was untouched and Node 2's mangoes were
    // replaced.
    /** @var \Drupal\node\NodeInterface $node1 */
    $node1 = $this->entityStorage()->load(1);
    $this->assertEquals('<p>Mango smoothie</p>', $node1->field_text_long[0]->value);

    /** @var \Drupal\node\NodeInterface $node2 */
    $node2 = $this->entityStorage()->load(2);
    $this->assertEquals(
      '<p>Not <a href="/replacement/smoothie">a replacement smoothie</a></p>',
      $node2->field_text_long[0]->value,
    );
  }

  /**
   * Tests search matching link but not body field produces no errors.
   *
   * Exercises the Node plugin string/link field code path with the
   * !empty($matches[0]) check.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testSearchMatchesLinkNotBody(): void {
    $this->drupalGet('admin/content/scanner');
    $this->assertSession()->statusCodeEquals(200);

    // "example.com" exists in the link field but not in body.
    $this->submitForm(['search' => 'example.com'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('An error has occurred.');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');
  }

  /**
   * Tests regex search with no matches produces no errors.
   *
   * Exercises the regex code path with empty preg_match results.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testRegexSearchNoMatches(): void {
    $this->drupalGet('admin/content/scanner');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'search' => '^zzz_no_match_pattern$',
      'regex' => 1,
    ];
    $this->submitForm($edit, 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('An error has occurred.');
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');
  }

  /**
   * Tests replace with non-matching text produces no errors.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testReplaceNoMatches(): void {
    $this->drupalGet('admin/content/scanner');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'search' => 'this text does not exist anywhere',
      'replace' => 'replacement text',
    ];
    $this->submitForm($edit, 'Replace');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Confirm');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('An error has occurred.');

    // Verify the original content is unchanged.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Body test');
  }

  /**
   * Tests search and replace when the same text appears in multiple fields.
   *
   * Verifies that all matching fields on a single entity are found and updated,
   * not just the last one searched.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSearchReplaceMultipleFields(): void {
    $marker = 'multi-field-scantest-marker';
    $node = $this->createNode([
      'type' => 'scanner_test_node_type',
      'title' => $marker,
      'field_string' => [['value' => $marker]],
      'field_string_long' => [['value' => $marker]],
    ]);
    $nid = $node->id();

    // All three fields should be found - 3 matches in 1 entity.
    $this->executeSearch($marker);
    $this->assertSession()->pageTextContains('Found 3 matches in 1 entities.');

    $replacedMarker = 'multi-field-scantest-replaced';
    // Replace must update all three fields.
    $this->executeReplacement($marker, $replacedMarker);
    $this->assertSession()->pageTextContains('1 entities processed.');

    $node = $this->entityStorage()->loadUnchanged($nid);
    $this->assertEquals($replacedMarker, $node->getTitle());
    $this->assertEquals($replacedMarker, $node->field_string->value);
    $this->assertEquals($replacedMarker, $node->field_string_long->value);

    // Original text must be gone.
    $this->executeSearch($marker);
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    // All three fields should be found - 3 matches in 1 entity.
    $this->executeSearch($replacedMarker);
    $this->assertSession()->pageTextContains('Found 3 matches in 1 entities.');
  }

  /**
   * Tests search and replace across multiple delta values of a single field.
   *
   * Verifies that every delta value within a single field on a single entity
   * is found and updated, not just the first matching delta.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSearchReplaceMultipleValuesOnSameField(): void {
    $marker = 'an apple';
    $node = $this->createNode([
      'type' => 'scanner_test_node_type',
      'title' => 'Multi-delta test node',
      'field_string' => [
        ['value' => 'Here is an apple pie'],
        ['value' => 'Here is an apple crumble'],
        ['value' => 'Here is an apple tart'],
      ],
    ]);
    $nid = $node->id();

    // All three deltas should be found - 3 matches in 1 entity.
    $this->executeSearch($marker);
    $this->assertSession()->pageTextContains('Found 3 matches in 1 entities.');

    $replaced = 'a cherry';
    $this->executeReplacement($marker, $replaced);
    $this->assertSession()->pageTextContains('1 entities processed.');

    // Every delta value must carry the replacement, none left with the marker.
    $node = $this->entityStorage()->loadUnchanged($nid);
    $this->assertEquals(
      [
        ['value' => 'Here is a cherry pie'],
        ['value' => 'Here is a cherry crumble'],
        ['value' => 'Here is a cherry tart'],
      ],
      $node->field_string->getValue(),
    );

    // Original text must be gone.
    $this->executeSearch($marker);
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    // All three replaced deltas should be found.
    $this->executeSearch($replaced);
    $this->assertSession()->pageTextContains('Found 3 matches in 1 entities.');
  }

  /**
   * Tests search and replace when multiple entities share the same field value.
   *
   * Verifies that the operation finds and updates every entity that carries
   * the search term in the field, not just the first one encountered.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testSearchReplaceMultipleEntitiesSameField(): void {
    $marker = 'vanilla cake';
    $node1 = $this->createNode([
      'type' => 'scanner_test_node_type',
      'title' => 'Multi-entity test node 1',
      'field_string' => [['value' => $marker]],
    ]);
    $node2 = $this->createNode([
      'type' => 'scanner_test_node_type',
      'title' => 'Multi-entity test node 2',
      'field_string' => [['value' => $marker]],
    ]);
    $node3 = $this->createNode([
      'type' => 'scanner_test_node_type',
      'title' => 'Multi-entity test node 3',
      'field_string' => [['value' => $marker]],
    ]);
    $nid1 = $node1->id();
    $nid2 = $node2->id();
    $nid3 = $node3->id();

    // One match per entity across three entities.
    $this->executeSearch($marker);
    $this->assertSession()->pageTextContains('Found 3 matches in 3 entities.');

    $replaced = 'chocolate cake';
    $this->executeReplacement($marker, $replaced);
    $this->assertSession()->pageTextContains('3 entities processed.');

    // Every node's field_string must carry the replacement.
    foreach ([$nid1, $nid2, $nid3] as $nid) {
      $node = $this->entityStorage()->loadUnchanged($nid);
      $this->assertEquals($replaced, $node->field_string->value);
    }

    // Original marker must be gone from all entities.
    $this->executeSearch($marker);
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    // All three replacements should be findable.
    $this->executeSearch($replaced);
    $this->assertSession()->pageTextContains('Found 3 matches in 3 entities.');
  }

  /**
   * Re-usable function test search and replace.
   *
   * Note: this is hardcoded to only expect one result.
   *
   * @todo Refactor to accommodate multiple entities and multiple matches.
   *
   * @param string $search
   *   Text to search for.
   * @param string $replace
   *   Text to replace search text with.
   * @param bool $first_run
   *   On the first execution the node will be created by anonymous, after the
   *   replacement is performed it will be owned by the current user. Therefore,
   *   identify whether this is the first time that a search is being performed
   *   on this node.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function searchAndReplace(string $search, string $replace, bool $first_run = TRUE): void {
    // Confirm the search string is in the node before any changes are made.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($search);

    // On the first run the node belongs to anonymous user.
    if ($first_run) {
      $this->assertEquals('0', $this->entityStorage()->loadUnchanged(1)->getRevisionUserId());
    }

    // Test the search operation.
    $this->executeSearch($search);

    // Make sure no errors were reported.
    $this->assertSession()->pageTextNotContains('An error has occurred.');
    $this->assertSession()->pageTextNotContains('Found 0 matches in 0 entities.');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    // Verify the result displays the node title and view/edit links.
    $this->assertSession()->pageTextContains('Title test');
    $this->assertSession()->linkExists('view');
    $this->assertSession()->linkExists('edit');
    $this->assertSession()->linkByHrefExists('/node/1');
    $this->assertSession()->linkByHrefExists('/node/1/edit');

    // Make sure the replacement string doesn't exist already.
    $this->executeSearch($replace);
    $this->assertSession()->pageTextNotContains('Found 1 matches in 1 entities.');
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    // Age the current revision so a fresh revision creation time is
    // detectable after the replacement.
    $node = $this->entityStorage()->loadUnchanged(1);
    $node->setNewRevision(FALSE);
    $node->setRevisionCreationTime(1);
    $node->save();

    // Test the replacement operation.
    $this->executeReplacement($search, $replace);
    $this->assertSession()->pageTextContains('1 entities processed.');
    // After search-replace, node revision author is user that did the
    // search-replace.
    $this->assertEquals($this->user2->id(), $this->entityStorage()->loadUnchanged(1)->getRevisionUserId());
    // The replacement stamps a fresh revision creation time, not the stale one.
    $this->assertGreaterThan(1, $this->entityStorage()->loadUnchanged(1)->getRevisionCreationTime());

    // Verify that the string changed.
    $this->executeSearch($search);
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');
    $this->executeSearch($replace);
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    // Verify that the node's text has changed.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($search);
    $this->assertSession()->responseContains($replace);
  }

}
