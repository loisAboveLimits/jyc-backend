<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\scanner\Attribute\Scanner;

/**
 * A Scanner plugin for handling Paragraph entities.
 */
#[Scanner(
  id: 'scanner_paragraph',
  type: 'paragraph',
)]
class Paragraph extends Entity {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function undo(array $data): void {
    // Determine the entity type from the plugin definition.
    $entity_type = $this->getPluginDefinition()['type'];

    try {
      // Load the specified paragraph revision.
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface */
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $revision = $entity_storage->loadRevision($data['old_vid']);
      $revision->setNewRevision(TRUE);
      // Set this revision as the current/default revision.
      $revision->isDefaultRevision(TRUE);
      $revision->save();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      $this->getLogger('scanner')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function handleParentRelationship(mixed $entity, array $values, array &$data): void {
    // Walk up the parent chain, updating each ancestor to reference the new
    // child revision, until the host entity (any type, any depth) is reached.
    $child = $entity;
    $remaining_depth = 20;

    while (method_exists($child, 'getParentEntity') && $remaining_depth-- > 0) {
      $parent = $child->getParentEntity();
      if (empty($parent)) {
        return;
      }

      $parent_field = $child->get('parent_field_name')->getString();
      if ($parent_field === '' || !$parent->hasField($parent_field)) {
        $this->getLogger('scanner')->notice('Unable to resolve the parent field for this paragraph (id: @id).', ['@id' => $child->id()]);
        return;
      }
      $index = $this->getMultiValueIndex($parent->get($parent_field)->getValue(), $child->id());
      if ($index < 0) {
        $this->getLogger('scanner')->notice('Unable to find the delta for this paragraph in the parent entity\'s field (id: @id).', ['@id' => $child->id()]);
        return;
      }

      $parent_key = $parent->getEntityTypeId() . ':' . $parent->id();
      $is_revisionable = $parent->getEntityType()->isRevisionable();

      if ($is_revisionable && !isset($data[$parent_key]['new_vid'])) {
        $data[$parent_key]['old_vid'] = $parent->getRevisionId();
        $parent->setNewRevision(TRUE);
        if ($parent instanceof RevisionLogInterface) {
          $parent->setRevisionLogMessage($this->t('Replaced @search with @replace via Scanner Search and Replace module.', [
            '@search' => $values['search'],
            '@replace' => $values['replace'],
          ]));
        }
        if (method_exists($parent, 'setRevisionUserId')) {
          $parent->setRevisionUserId($this->currentUser->id());
        }
      }

      $parent->get($parent_field)->set($index, [
        'target_id' => $child->id(),
        'target_revision_id' => $child->getRevisionId(),
      ]);
      $parent->save();

      if ($is_revisionable) {
        $data[$parent_key]['new_vid'] = $parent->getRevisionId();
      }

      // Stop at the host entity; only paragraphs have a parent to walk to.
      if (!method_exists($parent, 'getParentEntity')) {
        break;
      }
      $child = $parent;
    }
  }

  /**
   * Get multiple value index.
   */
  protected function getMultiValueIndex($values, $pid): int|string {
    foreach ($values as $key => $value) {
      if ($value['target_id'] == $pid) {
        return $key;
      }
    }
    return -1;
  }

}
