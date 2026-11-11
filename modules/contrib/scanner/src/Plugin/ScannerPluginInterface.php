<?php

namespace Drupal\scanner\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin for ScannerPluginInterface.
 */
interface ScannerPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Performs the search operation and returns the results.
   *
   * @param string $field
   *   The fully qualified name of the field (entityType:bundle:fieldname).
   * @param array $values
   *   The input values from the form ($form_state values).
   *
   * @return array
   *   An array containing the entity titles and an array of matches in the
   *   entity.
   */
  public function search(string $field, array $values): array;

  /**
   * Performs the replace operation using the search results.
   *
   * @param array $results_data
   *   A set of results data for a given entity type.
   * @param array $values
   *   The input values from the form ($form_state values).
   * @param array $undo_data
   *   The array for data values.
   *
   * @return array
   *   An array containing both the old and new revision IDs for each affected
   *   entity.
   */
  public function replace(array $results_data, array $values, array $undo_data): array;

  /**
   * Performs the undo operation.
   *
   * @param array $data
   *   An array containing the old and new revision id for the entity.
   */
  public function undo(array $data): void;

}
