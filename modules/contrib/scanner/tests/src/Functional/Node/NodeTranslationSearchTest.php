<?php

namespace Drupal\Tests\scanner\Functional\Node;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\scanner\Functional\ScannerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\filter\FilterFormatRepositoryInterface;

/**
 * Tests the search and replace functionality on nodes translations.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class NodeTranslationSearchTest extends ScannerTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'language',
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

    // Register French as a second language for translation tests.
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Creates test nodes with English and French translations.
   *
   * @param string $enText
   *   The text for the English body field.
   * @param string $frText
   *   The text for the French body field.
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array containing the created node entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createMultilingualNodes(string $enText, string $frText): array {
    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    $node1 = $this->createNode([
      'type' => 'scanner_test_node_type',
      'langcode' => 'en',
      'title' => 'Translation test node 1',
      'body' => [['value' => $enText, 'format' => $default_format]],
    ]);
    $node1->addTranslation('fr', [
      'title' => 'Noeud de test 1',
      'body' => [['value' => $frText, 'format' => $default_format]],
    ])->save();

    $node2 = $this->createNode([
      'type' => 'scanner_test_node_type',
      'langcode' => 'en',
      'title' => 'Translation test node 2',
      'body' => [['value' => $enText, 'format' => $default_format]],
    ]);
    $node2->addTranslation('fr', [
      'title' => 'Noeud de test 2',
      'body' => [['value' => $frText, 'format' => $default_format]],
    ])->save();

    return [$node1, $node2];
  }

  /**
   * Tests that search results are scoped to the requested language.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSearchByLanguage(): void {
    $enText = 'colour test';
    $frText = 'couleur test';

    $this->createMultilingualNodes($enText, $frText);

    // EN search finds EN content in both entities.
    $this->executeSearch('colour', ['language' => 'en']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // EN search does not find FR-only text.
    $this->executeSearch('couleur', ['language' => 'en']);
    $this->assertSession()->pageTextContains('Found 0 matches across 0 entities.');

    // FR search does not find EN-only text.
    $this->executeSearch('colour', ['language' => 'fr']);
    $this->assertSession()->pageTextContains('Found 0 matches across 0 entities.');

    // FR search finds FR translation content in both entities.
    $this->executeSearch('couleur', ['language' => 'fr']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // 'All languages' search finds EN content in both entities.
    $this->executeSearch('colour', ['language' => 'all']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // 'All languages' search finds FR content in both entities.
    $this->executeSearch('couleur', ['language' => 'all']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');
  }

  /**
   * Tests that replace scoped to a language only affects that translation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testReplaceInTranslation(): void {
    $originalEnText = 'colour test target';
    $replacedEnText = 'color test target';
    $frText = 'couleur test untouched';

    $nodes = $this->createMultilingualNodes($originalEnText, $frText);
    $nid1 = $nodes[0]->id();
    $nid2 = $nodes[1]->id();

    $this->executeReplacement($originalEnText, $replacedEnText, ['language' => 'en']);

    $node1 = $this->entityStorage()->loadUnchanged($nid1);
    $node2 = $this->entityStorage()->loadUnchanged($nid2);

    // EN body must be updated, FR translation body must be untouched.
    $this->assertSame($replacedEnText, $node1->getTranslation('en')->body->value);
    $this->assertSame($frText, $node1->getTranslation('fr')->body->value);

    // EN body must be updated, FR translation body must be untouched.
    $this->assertSame($replacedEnText, $node2->getTranslation('en')->body->value);
    $this->assertSame($frText, $node2->getTranslation('fr')->body->value);

    // Replaced text should be found in EN.
    $this->executeSearch($replacedEnText, ['language' => 'en']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // Original text should no longer be found in EN.
    $this->executeSearch($originalEnText, ['language' => 'en']);
    $this->assertSession()->pageTextContains('Found 0 matches across 0 entities.');

    // Replaced EN text should not leak into FR search.
    $this->executeSearch($replacedEnText, ['language' => 'fr']);
    $this->assertSession()->pageTextContains('Found 0 matches across 0 entities.');

    // Original FR text should still be found intact.
    $this->executeSearch($frText, ['language' => 'fr']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // Replaced text should be found when searching all languages.
    $this->executeSearch($replacedEnText, ['language' => 'all']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');

    // Original FR text should be found when searching all languages.
    $this->executeSearch($frText, ['language' => 'all']);
    $this->assertSession()->pageTextContains('Found 2 matches across 2 entities.');
  }

  /**
   * Tests that an "all languages" replace updates every translation.
   *
   * Regression coverage: replace() previously iterated language names, so an
   * "all languages" replace only touched the source translation and left the
   * others unchanged.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testReplaceUpdatesAllTranslations(): void {
    $enText = 'shared marker appears here';
    $frText = 'shared marker apparaît ici';

    $nodes = $this->createMultilingualNodes($enText, $frText);
    $nid1 = $nodes[0]->id();
    $nid2 = $nodes[1]->id();

    // Replace across all languages — both nodes match on both translations.
    $this->executeReplacement('shared marker', 'shared replacement', ['language' => 'all']);
    $this->assertSession()->pageTextContains('2 entities processed.');

    // Every translation of every matched node must carry the replacement,
    // and none should still hold the original term.
    foreach ([$nid1, $nid2] as $nid) {
      $node = $this->entityStorage()->loadUnchanged($nid);
      $english = $node->getTranslation('en')->body->value;
      $french = $node->getTranslation('fr')->body->value;

      $this->assertStringContainsString('shared replacement', $english);
      $this->assertStringNotContainsString('shared marker', $english);
      $this->assertStringContainsString('shared replacement', $french);
      $this->assertStringNotContainsString('shared marker', $french);
    }
  }

}
