<?php

namespace Drupal\scanner\Hook;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Hook\Attribute\Hook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Query hook implementations for the Scanner module.
 */
class ScannerQueryHooks {

  public function __construct(
    #[Autowire(service: 'entity_type.manager')]
    private readonly EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'database')]
    private readonly Connection $database,
  ) {}

  /**
   * Implements hook_query_TAG_alter() for 'scanner_search_regexp_like'.
   */
  #[Hook('query_scanner_search_regexp_like_alter')]
  public function queryAlter(AlterableInterface $query): void {
    if (!$query instanceof SelectInterface) {
      return;
    }
    $this->addRegexpLikeCondition($query);
  }

  /**
   * Adds a WHERE condition using the REGEXP_LIKE() function.
   */
  private function addRegexpLikeCondition(SelectInterface $query): void {
    try {
      [
        'entity_type_id' => $entity_type_id,
        'fieldname' => $fieldname,
        'langcode' => $langcode,
        'mode' => $mode,
        'pattern' => $pattern,
      ] = $query->getMetaData('scanner_search_regexp_like');

      $entity_query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
      // It will only work with an SQL entity query.
      if (!$entity_query instanceof Query) {
        return;
      }

      // Use the entity query helper to add the field to $query.
      $tables = $entity_query->getTables($query);
      $field = $tables->addField($fieldname, 'INNER', $langcode);

      // Escape the field name on Drupal 9+.
      // https://www.drupal.org/node/2986894
      $field = $this->database->escapeField($field);

      // Add the conditional expression.
      $query->where("REGEXP_LIKE($field, :pattern, :match_type)", [
        ':match_type' => $mode ? 'c' : 'i',
        ':pattern' => $pattern,
      ]);
    }
    catch (QueryException $e) {

    }
  }

}
