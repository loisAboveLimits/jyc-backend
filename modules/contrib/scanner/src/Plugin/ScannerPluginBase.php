<?php

namespace Drupal\scanner\Plugin;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\scanner\WordBoundariesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for ScannerPluginBase class.
 */
abstract class ScannerPluginBase extends PluginBase implements ScannerPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a ScannerPluginBase object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStore
   *   The temporary store.
   * @param \Drupal\scanner\Plugin\ScannerPluginManager $scannerManager
   *   The scanner manager.
   * @param \Drupal\scanner\WordBoundariesHelper $scannerHelper
   *   The scanner.word_boundaries_helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The currentUser.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected PrivateTempStoreFactory $tempStore,
    protected ScannerPluginManager $scannerManager,
    protected WordBoundariesHelper $scannerHelper,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DateFormatterInterface $dateFormatter,
    protected AccountProxyInterface $currentUser,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private'),
      $container->get('plugin.manager.scanner'),
      $container->get('scanner.word_boundaries_helper'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('datetime.time')
    );
  }

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
  abstract public function search(string $field, array $values): array;

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
  abstract public function replace(array $results_data, array $values, array $undo_data): array;

  /**
   * Performs the undo operation.
   *
   * @param array $data
   *   An array containing the old and new revision id for the entity.
   */
  abstract public function undo(array $data): void;

  /**
   * Helper function to handle entity reference relationships.
   *
   * @param mixed $entity
   *   The entity that was updated.
   * @param array $values
   *   An array containing the input values from the form.
   * @param array $data
   *   An array containing the revision ID's for the entities being processed.
   */
  abstract protected function handleParentRelationship(mixed $entity, array $values, array &$data): void;

}
