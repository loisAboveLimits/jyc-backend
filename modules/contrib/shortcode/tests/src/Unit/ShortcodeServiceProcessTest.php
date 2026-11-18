<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcode\Unit;

use Drupal\filter\Plugin\FilterInterface;
use Drupal\shortcode\Plugin\ShortcodeInterface;
use Drupal\shortcode\ShortcodePluginManager;
use Drupal\shortcode\ShortcodeService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Characterizes ShortcodeService::process() ahead of decomposing it.
 */
#[Group('shortcode')]
#[CoversClass(ShortcodeService::class)]
class ShortcodeServiceProcessTest extends UnitTestCase {

  /**
   * Builds a shortcode plugin definition, as returned by getDefinitions().
   */
  protected function createDefinition(string $id): array {
    return [
      'id' => $id,
      'token' => $id,
      'weight' => 99,
      'provider' => 'shortcode_basic_tags',
    ];
  }

  /**
   * Builds a ShortcodeService backed by a stub plugin manager.
   */
  protected function createShortcodeService(array $definitions, array $plugins): ShortcodeService {
    $pluginManager = $this->createMock(ShortcodePluginManager::class);
    $pluginManager->method('getDefinitions')->willReturn($definitions);
    $pluginManager->method('createInstance')->willReturnCallback(
      static fn (string $plugin_id) => $plugins[$plugin_id]
    );
    return new ShortcodeService($pluginManager);
  }

  /**
   * Builds a fake shortcode plugin that wraps its text in <token> tags.
   */
  protected function createEchoPlugin(string $token): ShortcodeInterface {
    $plugin = $this->createMock(ShortcodeInterface::class);
    $plugin->method('process')->willReturnCallback(
      static fn (array $attributes, string $text = '') => "<$token>" . $text . "</$token>"
    );
    return $plugin;
  }

  /**
   * Tests that a simple opening/closing tag pair is replaced.
   */
  public function testSimpleTagPairIsReplaced(): void {
    $definitions = ['quote' => $this->createDefinition('quote')];
    $service = $this->createShortcodeService($definitions, ['quote' => $this->createEchoPlugin('quote')]);
    $this->assertSame('<quote>Hello</quote>', $service->process('[quote]Hello[/quote]'));
  }

  /**
   * Tests that a self-closing tag is replaced.
   */
  public function testSelfClosingTagIsReplaced(): void {
    $definitions = ['img' => $this->createDefinition('img')];
    $service = $this->createShortcodeService($definitions, ['img' => $this->createEchoPlugin('img')]);
    $this->assertSame('before <img></img> after', $service->process('before [img src="x.png" /] after'));
  }

  /**
   * Tests that nested tags are both processed.
   */
  public function testNestedTagsAreBothProcessed(): void {
    $definitions = [
      'item' => $this->createDefinition('item'),
      'quote' => $this->createDefinition('quote'),
    ];
    $plugins = ['item' => $this->createEchoPlugin('item'), 'quote' => $this->createEchoPlugin('quote')];
    $service = $this->createShortcodeService($definitions, $plugins);
    $this->assertSame('<item><quote>inner</quote></item>', $service->process('[item][quote]inner[/quote][/item]'));
  }

  /**
   * Tests that a double-bracket-escaped tag is left as plain text.
   */
  public function testEscapedTagIsNotProcessed(): void {
    $definitions = ['quote' => $this->createDefinition('quote')];
    $service = $this->createShortcodeService($definitions, ['quote' => $this->createEchoPlugin('quote')]);
    $this->assertSame('[quote]Hello[/quote]', $service->process('[[quote]]Hello[[/quote]]'));
  }

  /**
   * Tests that an unrecognized tag is left fully intact, brackets and all.
   */
  public function testUnknownTagIsLeftAsPlainText(): void {
    $service = $this->createShortcodeService([], []);
    $this->assertSame('[nope]Hello[/nope]', $service->process('[nope]Hello[/nope]'));
  }

  /**
   * Tests that a registered but filter-disabled tag strips its wrapper.
   *
   * Unlike an unrecognized token (testUnknownTagIsLeftAsPlainText()), a
   * registered token that just isn't enabled on the given filter has its
   * wrapping tags stripped, leaving the inner text bare.
   */
  public function testTagDisabledOnFilterStripsWrapperKeepsInnerText(): void {
    $definitions = ['quote' => $this->createDefinition('quote')];
    $service = $this->createShortcodeService($definitions, []);
    $filter = $this->createMock(FilterInterface::class);
    $filter->method('getPluginId')->willReturn('shortcode');
    $filter->method('getConfiguration')->willReturn(['settings' => []]);
    $this->assertSame('Hello', $service->process('[quote]Hello[/quote]', 'en', $filter));
  }

}
