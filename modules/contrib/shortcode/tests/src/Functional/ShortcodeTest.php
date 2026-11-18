<?php

namespace Drupal\Tests\shortcode\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Drupal 8 shortcode module functionality.
 *
 * @group shortcode
 */
#[Group('shortcode')]
#[RunTestsInSeparateProcesses]
class ShortcodeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Use standard install profile that include page content type.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array<string>
   */
  protected static $modules = ['filter', 'shortcode', 'shortcode_basic_tags'];

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Url of the site.
   *
   * @var string|null
   */
  private $siteUrl;

  /**
   * A session page.
   *
   * @var \Behat\Mink\Element\DocumentElement|null
   */
  private $sessionPage;

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->siteUrl = Url::fromRoute('<front>', [], ["absolute" => TRUE])->toString();

    // The 'standard' profile no longer ships the 'page' content type itself
    // (it moved into the core/recipes/page_content_type recipe), and
    // BrowserTestBase's install path never applies profile recipes, so
    // 'create page content' would otherwise not exist as a permission.
    if (!NodeType::load('page')) {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    }

    // Create a text format and enable the shortcode filter.
    $format = FilterFormat::create([
      'format' => 'custom_format',
      'name' => 'Custom format',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<a href> <div class> <p> <br>',
          ],
        ],
        'shortcode' => [
          'status' => 1,
          'settings' => [
            'link' => 1,
            'random' => 1,
            'img' => 1,
            'clear' => 1,
            'dropcap' => 1,
            'item' => 1,
            'highlight' => 1,
            'button' => 1,
            'quote' => 1,
            'block' => 1,
          ],
        ],
      ],
    ]);
    $format->save();

    // Create a user with required permissions.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      $format->getPermissionName(),
    ]);
    $this->drupalLogin($this->webUser);

    $this->sessionPage = $this->getSession()->getPage();
  }

  /**
   * Return test page with the given content.
   */
  private function createTestNode(string $contents): NodeInterface {
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test Button Link';
    $settings['body'] = [
      'value' => $contents,
      'format' => 'custom_format',
    ];

    return $this->drupalCreateNode($settings);
  }

  /**
   * Tests that the Button shortcode returns the right content.
   */
  public function testButtonShortcode() {

    $sets = [
      [
        'input' => '[button]Label[/button]',
        'output' => '<a href="' . $this->siteUrl . '" class=" button" title="Label"><span>Label</span></a>',
        'message' => 'Button shortcode output matches.',
      ],
      [
        'input' => '[button path="<front>" class="custom-class"]Label[/button]',
        'output' => '<a href="' . $this->siteUrl . '" class="custom-class button" title="Label"><span>Label</span></a>',
        'message' => 'Button shortcode with custom class output matches.',
      ],
      [
        'input' => '[button path="http://www.google.com" class="custom-class" title="Title" id="theLabel" style="border-radius:5px;"]Label[/button]',
        'output' => '<a href="http://www.google.com" class="custom-class button" id="theLabel" style="border-radius:5px;" title="Title"><span>Label</span></a>',
        'message' => 'Button shortcode with custom attributes and absolute output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', 'a.button');
      $this->assertEquals($set['output'], $element->getOuterHtml(), $set['message']);
    }
  }

  /**
   * Tests that the Clear shortcode returns the right content.
   */
  public function testClearShortcode() {

    $sets = [
      [
        'input' => '[clear]<div>Other elements</div>[/clear]',
        'output' => '<div class=" clearfix"><div>Other elements</div></div>',
        'message' => 'Clear shortcode output matches.',
      ],
      [
        'input' => '[clear type="s"]<div>Other elements</div>[/clear]',
        'output' => '<span class=" clearfix"><div>Other elements</div></span>',
        'message' => 'Clear shortcode with custom type "s" output matches.',
      ],
      [
        'input' => '[clear type="span"]<div>Other elements</div>[/clear]',
        'output' => '<span class=" clearfix"><div>Other elements</div></span>',
        'message' => 'Clear shortcode with custom type "span" output matches.',
      ],
      [
        'input' => '[clear type="d"]<div>Other elements</div>[/clear]',
        'output' => '<div class=" clearfix"><div>Other elements</div></div>',
        'message' => 'Clear shortcode with custom type "d" output matches.',
      ],
      [
        'input' => '[clear type="d" class="custom-class" id="theLabel" style="background-color: #F00;"]<div>Other elements</div>[/clear]',
        'output' => '<div class="custom-class clearfix" id="theLabel" style="background-color: #F00;"><div>Other elements</div></div>',
        'message' => 'Clear shortcode with custom attributes output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.clearfix');
      $this->assertEquals($set['output'], $element->getOuterHtml(), $set['message']);
    }
  }

  /**
   * Tests that the Dropcap shortcode returns the right content.
   */
  public function testDropcapShortcode() {

    $sets = [
      [
        'input' => '[dropcap]text[/dropcap]',
        'output' => '<span class=" dropcap">text</span>',
        'message' => 'Dropcap shortcode output matches.',
      ],
      [
        'input' => '[dropcap class="custom-class"]text[/dropcap]',
        'output' => '<span class="custom-class dropcap">text</span>',
        'message' => 'Dropcap shortcode with custom class output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.dropcap');
      $this->assertEquals($set['output'], $element->getOuterHtml(), $set['message']);
    }
  }

  /**
   * Tests that the highlight shortcode returns the right content.
   */
  public function testHighlightShortcode() {

    $sets = [
      [
        'input' => '[highlight]highlighted text[/highlight]',
        'output' => '<span class=" highlight">highlighted text</span>',
        'message' => 'Highlight shortcode output matches.',
      ],
      [
        'input' => '[highlight class="custom-class"]highlighted text[/highlight]',
        'output' => '<span class="custom-class highlight">highlighted text</span>',
        'message' => 'Highlight shortcode with custom class output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.highlight');
      $this->assertEquals($set['output'], $element->getOuterHtml(), $set['message']);
    }
  }

  /**
   * Tests that the Image shortcode returns the right content.
   */
  public function testImgShortcode() {

    $sets = [
      [
        'input' => '[img src="/abc.jpg" alt="Test image" /]',
        'output' => '<img src="/abc.jpg" class=" img" alt="Test image">',
        'message' => 'Image shortcode output matches.',
      ],
      [
        'input' => '[img src="/abc.jpg" class="custom-class" alt="Test image" /]',
        'output' => '<img src="/abc.jpg" class="custom-class img" alt="Test image">',
        'message' => 'Image shortcode with custom class output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.img');
      $this->assertEquals($set['output'], $element->getOuterHtml(), $set['message']);
    }
  }

  /**
   * Tests that the Item shortcode returns the right content.
   */
  public function testItemShortcode() {

    $sets = [
      [
        'input' => '[item class="item-class-here"]Item body here[/item]',
        'output' => '<div class="item-class-here">Item body here</div>',
        'message' => 'Item shortcode output matches.',
      ],
      [
        'input' => '[item type="s" class="item-class-here"]Item body here[/item]',
        'output' => '<span class="item-class-here">Item body here</span>',
        'message' => 'Item shortcode with custom type "s" output matches.',
      ],
      [
        'input' => '[item class="item-class-here" type="d" style="background-color:#F00"]Item body here[/item]',
        'output' => '<div class="item-class-here" style="background-color:#F00">Item body here</div>',
        'message' => 'Item shortcode with custom type "d" and class and styles output matches.',
      ],
      [
        'input' => '[item class="item-class-here" type="s" style="background-color:#F00"]Item body here[/item]',
        'output' => '<span class="item-class-here" style="background-color:#F00">Item body here</span>',
        'message' => 'Item shortcode with custom type "s" and class and styles output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.item-class-here');
      $this->assertEquals($set['output'], $element->getOuterHtml(), $set['message']);
    }
  }

  /**
   * Tests that the Link shortcode returns the right content.
   */
  public function testLinkShortcode() {

    $sets = [
      [
        'input' => '[link path="node/1" class="link-class"]Label[/link]',
        'output' => '<a href="' . $this->siteUrl . 'node/1" class="link-class" title="Label">Label</a>',
        'message' => 'Link shortcode output matches.',
      ],
      [
        'input' => '[link path="node/23" title="Google" class="link-class" style="background-color:#0FF;"] Label [/link]',
        'output' => '<a href="' . $this->siteUrl . 'node/23" class="link-class" style="background-color:#0FF;" title="Google"> Label </a>',
        'message' => 'Link shortcode with title and attributes output matches.',
      ],
      [
        'input' => '[link url="http://google.com" title="Google" class="link-class" style="background-color:#0FF;"] Label [/link]',
        'output' => '<a href="http://google.com" class="link-class" style="background-color:#0FF;" title="Google"> Label </a>',
        'message' => 'Link shortcode with url, title and attributes output matches.',
      ],
      [
        'input' => '[link path="node/23" url="http://google.com" title="Google" class="link-class" style="background-color:#0FF;"] Label [/link]',
        'output' => '<a href="http://google.com" class="link-class" style="background-color:#0FF;" title="Google"> Label </a>',
        'message' => 'Link shortcode with both path and url, title and attributes output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.link-class');
      $this->assertEquals($set['output'], $element->getOuterHtml(), $set['message']);
    }
  }

  /**
   * Tests that the Quote shortcode returns the right content.
   */
  public function testQuoteShortcode() {

    $sets = [
      [
        'input' => '[quote]This is by no one[/quote]',
        'output' => '<span class=" quote"> This is by no one </span>',
        'message' => 'Quote shortcode output matches.',
      ],
      [
        'input' => '[quote class="test-quote"]This is by no one[/quote]',
        'output' => '<span class="test-quote quote"> This is by no one </span>',
        'message' => 'Quote shortcode with class output matches.',
      ],
      [
        'input' => '[quote class="test-quote" author="ryan"]This is by ryan[/quote]',
        'output' => '<span class="test-quote quote"> <span class="quote-author">ryan wrote: </span> This is by ryan </span>',
        'message' => 'Quote shortcode with class and author output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.quote');
      $element = preg_replace('/\s+/', ' ', $element->getOuterHtml());
      $this->assertEquals($set['output'], $element, $set['message']);
    }
  }

  /**
   * Tests that the Random shortcode returns the right length.
   */
  public function testRandomShortcode() {

    $sets = [
      [
        'input' => '<div class="random-shortcode">[random/]</div>',
        'output' => 8,
        'message' => 'Random shortcode self-closed, output length.',
      ],
      [
        'input' => '<div class="random-shortcode">[random][/random]</div>',
        'output' => 8,
        'message' => 'Random shortcode output length.',
      ],
      [
        'input' => '<div class="random-shortcode">[random length=10][/random]</div>',
        'output' => 10,
        'message' => 'Random shortcode with custom length, output length.',
      ],
    ];

    foreach ($sets as $set) {
      $node = $this->createTestNode($set['input']);
      $this->drupalGet('node/' . $node->id());
      $element = $this->sessionPage->find('css', '.random-shortcode');
      $this->assertEquals($set['output'], strlen($element->getText()), $set['message']);
    }
  }

}
