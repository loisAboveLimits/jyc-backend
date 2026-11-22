<?php

namespace Drupal\Tests\scanner\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\scanner\AdminHelper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the output of search and replace results.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class ScannerResultsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'scanner',
    'system',
  ];

  /**
   * Tests that the results are grouped by entity type.
   */
  public function testGroups(): void {
    $results = [
      'node' => $this->buildResults('Content', 2, 3),
      'block_content' => $this->buildResults('Content block', 1, 1),
    ];
    $build = AdminHelper::buildResults($results);
    $this->render($build);

    $this->assertText('Found 7 matches across 3 entities.');

    // Each entity type has a group, with its counts in the summary.
    $groups = $this->xpath('//div[@class="scanner-results"]/details');
    $this->assertCount(2, $groups);
    $this->assertSame('Content block (1 entity, 1 match)', trim((string) $groups[0]->summary));
    $this->assertSame('Content (2 entities, 6 matches)', trim((string) $groups[1]->summary));

    // Small result sets are shown in full.
    $this->assertCount(2, $this->xpath('//details[@open]'));

    // The matches are listed in their group.
    $this->assertCount(1, $groups[0]->xpath('.//ul/li[a]'));
    $this->assertCount(2, $groups[1]->xpath('.//ul/li[a]'));
    $this->assertCount(2, $this->xpath('//details[2]//li/strong[text()="Match 3"]'));
    $this->assertCount(1, $this->xpath('//details[1]//a[@href="/node/1/edit" and text()="edit"]'));
  }

  /**
   * Tests that all groups are closed for large result sets.
   */
  public function testLargeResults(): void {
    $results = [
      'node' => $this->buildResults('Content', AdminHelper::COLLAPSE_RESULTS_LIMIT, 1),
      'block_content' => $this->buildResults('Content block', 1, 1),
    ];
    $build = AdminHelper::buildResults($results);
    $this->render($build);

    $this->assertCount(2, $this->xpath('//details'));
    $this->assertCount(0, $this->xpath('//details[@open]'));

    // At the limit, the groups are still open.
    array_pop($results['node']['results']);
    $build = AdminHelper::buildResults($results);
    $this->render($build);
    $this->assertCount(2, $this->xpath('//details[@open]'));
  }

  /**
   * Tests that tags in matches don't affect the rest of the results.
   */
  public function testUnclosedTags(): void {
    $results = [
      'block_content' => $this->buildResults('Content block', 1, 1),
      'node' => $this->buildResults('Content', 1, 1),
    ];
    // Matches are excerpts of field values, so can start or end mid-markup.
    $results['block_content']['results'][1]['fields']['body']['results'] = [
      '<strong>Health care <strong>match</strong> is',
      'stray</strong> text <strong>match</strong>',
      '<p>A <em>paragraph</em> <strong>match</strong>',
    ];
    $build = AdminHelper::buildResults($results);
    $this->render($build);

    $content = $this->getRawContent();
    $this->assertStringContainsString('<strong>Health care <strong>match</strong> is</strong>', $content);
    $this->assertStringContainsString('stray text <strong>match</strong>', $content);
    $this->assertStringContainsString('A paragraph <strong>match</strong>', $content);
    $this->assertSame(substr_count($content, '<strong>'), substr_count($content, '</strong>'));

    // Each group still starts with its summary.
    $this->assertCount(2, $this->xpath('//details/summary'));
  }

  /**
   * Builds the results for one entity type, as returned by a Scanner plugin.
   *
   * @param string $label
   *   The entity type label.
   * @param int $entities
   *   The number of matching entities.
   * @param int $matches
   *   The number of matches in each entity.
   *
   * @return array
   *   The results.
   */
  protected function buildResults(string $label, int $entities, int $matches): array {
    $results = [
      'type' => strtolower($label),
      'label' => $label,
      'results' => [],
    ];
    for ($id = 1; $id <= $entities; $id++) {
      $field_results = [];
      for ($match = 1; $match <= $matches; $match++) {
        $field_results[] = "Text with <strong>Match $match</strong>";
      }
      $results['results'][$id] = [
        'title' => "$label $id",
        'url' => "/node/$id",
        'edit_url' => "/node/$id/edit",
        'bundle' => 'Page',
        'fields' => [
          'body' => [
            'label' => 'Body',
            'results' => $field_results,
          ],
        ],
      ];
    }
    return $results;
  }

}
