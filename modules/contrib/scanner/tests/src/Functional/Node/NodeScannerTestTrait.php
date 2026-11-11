<?php

namespace Drupal\Tests\scanner\Functional\Node;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\node\NodeInterface;

/**
 * Node-specific helpers for scanner functional tests.
 */
trait NodeScannerTestTrait {

  /**
   * Admin user created during node scanner setup.
   */
  protected AccountInterface $user1;

  /**
   * Search-and-replace user created during node scanner setup.
   */
  protected AccountInterface $user2;

  /**
   * Setup components needed for node scanner tests.
   *
   * Name intentionally does not start with "test" — otherwise PHPUnit would
   * discover it as a test method in every subclass and try to run it after
   * setUp() has already invoked it, blowing up on duplicate entity creation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUpNodeScanner(): void {
    // Create a test content type.
    $content_type = 'scanner_test_node_type';
    $this->createContentType([
      'type' => $content_type,
      'name' => $content_type,
    ]);
    $this->addTestFields('node', $content_type);

    $default_format = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format());

    // Create a node in this content type.
    $node_args = [
      'type' => $content_type,
      'title' => 'Title test',
      'body' => [
        [
          'value' => 'Body test',
          'format' => $default_format,
        ],
      ],
    ] + $this->standardEntityValues();
    $this->createNode($node_args);

    // Create a second node, work from the first args and just change a few
    // values.
    $node_args['title'] = 'Another title test';
    $node_args['body'][0]['value'] = 'Another body test';
    $node_args['field_link'][0]['uri'] = 'https://theexample.com';
    $node_args['field_string'] = [
      ['value' => 'Short string test #1'],
      ['value' => 'Short string test #2'],
      ['value' => 'Short string test #3'],
    ];
    $node_args['field_string_long'] = [
      ['value' => 'Longer string test #1'],
      ['value' => 'Longer string test #2'],
      ['value' => 'Longer string test #3'],
    ];
    $node_args['field_text_long'] = [
      [
        'value' => '<p>Not <a href="/mango/smoothie">a mango smoothie</a></p>',
        'format' => $default_format,
      ],
      [
        'value' => '<p>Not <a href="/banana/split">a banana split</a></p>',
        'format' => $default_format,
      ],
      [
        'value' => '<p>Not <a href="/egg/custard">an egg custard</a></p>',
        'format' => $default_format,
      ],
    ];
    $this->createNode($node_args);

    // Log in as an admin who can modify the module's settings.
    $user_1 = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($user_1);
    $this->user1 = $user_1;

    // Enable the content type and every scannable field on it.
    $fields = [];
    foreach (array_keys($node_args) as $field_name) {
      if ($field_name === 'type') {
        continue;
      }
      $fields[] = "node:scanner_test_node_type:$field_name";
    }
    $this->configureScannerScope(['node:scanner_test_node_type'], $fields);

    // Log in as a user that can use the "replace" system.
    $user_2 = $this->createUser([
      // 'administer nodes',
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($user_2);
    $this->user2 = $user_2;
  }

  /**
   * Create a content type and a node.
   *
   * @param string $title
   *   A title for the node that will be returned.
   * @param string $value
   *   The text to use as the body.
   * @param string $content_type
   *   The node bundle type.
   * @param string $content_type_label
   *   The content type label.
   *
   * @return \Drupal\node\NodeInterface
   *   A fully formatted node object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createContentTypeNode(string $title, string $value, string $content_type, string $content_type_label): NodeInterface {
    // Create a content type with a body field.
    $this->createContentType([
      'type' => $content_type,
      'name' => $content_type_label,
    ]);

    // Add the standard test fields.
    $this->addTestFields('node', $content_type);

    // Create a node in this content type.
    $args = [
      'type' => $content_type,
      'title' => $title,
      'body' => [
        [
          'value' => $value,
          'format' => DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format()),
        ],
      ],
      'field_link' => [
        [
          'uri' => 'https://example.com',
        ],
      ],
    ];
    return $this->createNode($args);
  }

}
