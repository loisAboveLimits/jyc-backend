<?php

namespace Drupal\Tests\scanner\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the "Preceded by" and "Followed by" search options.
 *
 * @group scanner
 */
#[Group('scanner')]
#[RunTestsInSeparateProcesses]
class ScannerSurroundingTextTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'scanner',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['scanner']);
  }

  /**
   * Tests that only the search text is matched and replaced.
   */
  public function testSurroundingText(): void {
    $text = 'The quick brown fox jumps over the brown dog.';

    foreach (['preceded' => ['quick ', ''], 'followed' => ['', ' fox']] as $option => [$preceded, $followed]) {
      foreach ([FALSE, TRUE] as $wholeword) {
        foreach ([FALSE, TRUE] as $regex) {
          $message = sprintf('%s, whole word: %s, regex: %s', $option, var_export($wholeword, TRUE), var_export($regex, TRUE));
          $regex_pattern = $this->buildCondition('brown', $wholeword, $regex, $preceded, $followed)['phpRegex'];

          // Only the first "brown" has the surrounding text, and the
          // surrounding text isn't part of the match.
          $this->assertSame(1, preg_match_all($regex_pattern, $text, $matches), $message);
          $this->assertSame(['brown'], $matches[0], $message);
          $this->assertSame('The quick red fox jumps over the brown dog.', preg_replace($regex_pattern, 'red', $text), $message);
        }
      }
    }
  }

  /**
   * Tests whole word searches that start or end with non-word characters.
   */
  public function testWholewordNonWordEdges(): void {
    $text = '<a href="https://example.com/some/page/">page</a> <a href="/egg/custard">custard</a>';

    $cases = [
      // Trailing non-word character.
      'https://example.com/some/page/' => 1,
      // Leading non-word character.
      '/egg/custard' => 1,
      // Word characters on both edges keep the word boundaries.
      'https://example.com/some/page' => 1,
      'https://example.com/some/pag' => 0,
      'gg/custard' => 0,
    ];
    foreach ($cases as $search => $expected) {
      $condition = $this->buildCondition($search, TRUE, FALSE, '', '');
      $this->assertSame($expected, preg_match_all($condition['phpRegex'], $text), $search);
    }
  }

  /**
   * Builds the search condition as the node Scanner plugin does.
   *
   * @param string $search
   *   The text to search for.
   * @param bool $wholeword
   *   Whether to match whole words only.
   * @param bool $regex
   *   Whether the search text is a regular expression.
   * @param string $preceded
   *   The text that must precede the search text.
   * @param string $followed
   *   The text that must follow the search text.
   *
   * @return array
   *   The condition, as returned by
   *   \Drupal\scanner\Plugin\Scanner\Entity::buildCondition().
   */
  protected function buildCondition(string $search, bool $wholeword, bool $regex, string $preceded, string $followed): array {
    $plugin = $this->container->get('plugin.manager.scanner')->createInstance('scanner_node');
    return (fn() => $this->buildCondition($search, FALSE, $wholeword, $regex, $preceded, $followed))->call($plugin);
  }

}
