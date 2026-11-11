<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Taxonomy term entities.
 */
#[Scanner(
  id: 'scanner_taxonomy_term',
  type: 'taxonomy_term',
)]
class Taxonomy extends Entity {

  /**
   * {@inheritdoc}
   */
  protected string $bundleKey = 'vid';

}
