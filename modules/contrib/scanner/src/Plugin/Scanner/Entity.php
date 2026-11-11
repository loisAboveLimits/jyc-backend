<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\scanner\AdminHelper;
use Drupal\scanner\Plugin\ScannerPluginBase;

/**
 * A base entity Scanner plugin for handling entities.
 */
abstract class Entity extends ScannerPluginBase {

  use LoggerChannelTrait;
  use MessengerTrait;

  /**
   * The scanner regular expression.
   *
   * @var string
   */
  protected string $scannerRegexChars = '.\/+*?[^]$() {}=!<>|:';

  /**
   * {@inheritdoc}
   */
  public function search(string $field, array $values): array {
    $results = [];

    // Determine the entity type from the plugin definition.
    $entity_type = $this->getPluginDefinition()['type'];

    try {
      // $field will be string composed of entity type, bundle name, and field
      // name delimited by ':' characters. Ignore the entity type element as
      // this logic will use the class attribute instead.
      [, $bundle, $field_name] = explode(':', $field);

      $storage = $this->entityTypeManager->getStorage($entity_type);
      $query = $storage->getQuery();
      $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
      $query->condition($bundle_key, $bundle);
      if ($values['published']) {
        $query->condition('status', 1);
      }
      $conditionVals = $this->buildCondition($values['search'], $values['mode'], $values['wholeword'], $values['regex'], $values['preceded'], $values['followed']);
      $this->addQueryCondition($query, $conditionVals, $entity_type, $field_name, $values['mode'], $values['language']);

      // Disable the normal access check.
      $query->accessCheck(FALSE);

      $entity_ids = $query->execute();

      // This will be loaded from the first matched entity.
      $field_definition = NULL;

      if (!empty($entity_ids)) {
        // Load the entities which match the criteria.
        $entities = $storage->loadMultiple($entity_ids);

        // Iterate over matched entities to extract information that will be
        // rendered in the results.
        foreach ($entities as $id => $entity) {
          if (!empty($entity)) {
            $translation_entities = [];
            if ($values['language'] !== 'all') {
              // Load the translation for the requested language.
              if ($entity->hasTranslation($values['language'])) {
                $entity = $entity->getTranslation($values['language']);
              }
              $translation_entities[] = $entity;
            }
            else {
              // We need to extract matches from every translation.
              foreach ($entity->getTranslationLanguages() as $langcode => $language) {
                $translation_entities[] = $entity->getTranslation($langcode);
              }
            }

            // In Drupal 11 the full bundle entity definition is available.
            if (method_exists($entity, 'getBundleEntity')) {
              /** @var \Drupal\Core\Entity\EntityInterface $bundle */
              $bundle = $entity->getBundleEntity()->label();
            }

            // Load this field's definition, if not loaded before.
            if (!$field_definition) {
              $field_definition = $entity->getFieldDefinition($field_name);
            }

            // Optionally link to a root entity, e.g. a node that a paragraph is
            // attached to.
            if (method_exists($entity, 'getParentEntity')) {
              $root_entity = $entity->getParentEntity();
              if (!$root_entity) {
                $this->getLogger('scanner')->notice('Unable to load the parent entity for @entity_type #@entity_id, which suggests a data corruption problem.', [
                  '@entity_type' => $entity_type,
                  '@entity_id' => $id,
                ]);
                continue;
              }
              while (!empty($root_entity) && method_exists($root_entity, 'getParentEntity')) {
                $root_entity = $root_entity->getParentEntity();
                if (!$root_entity) {
                  $this->getLogger('scanner')->notice('Unable to load the parent entity for @entity_type #@entity_id, which suggests a data corruption problem.', [
                    '@entity_type' => $entity_type,
                    '@entity_id' => $id,
                  ]);
                  continue 2;
                }
              }
              $results['results'][$id]['title'] = $root_entity->label();
              $results['results'][$id]['url'] = $root_entity->hasLinkTemplate('canonical') ? $root_entity->toUrl()->toString() : NULL;
              $results['results'][$id]['edit_url'] = $root_entity->hasLinkTemplate('edit-form') ? $root_entity->toUrl('edit-form')->toString() : NULL;
            }

            // Link to the entity itself.
            else {
              $results['results'][$id]['title'] = $entity->label();
              $results['results'][$id]['url'] = $entity->hasLinkTemplate('canonical') ? $entity->toUrl()->toString() : NULL;
              $results['results'][$id]['edit_url'] = $entity->hasLinkTemplate('edit-form') ? $entity->toUrl('edit-form')->toString() : NULL;
            }

            // Add some variables about the entity itself.
            $results['type'] = $entity_type;
            $results['label'] = $entity->getEntityType()->getLabel();
            $results['results'][$id]['bundle'] = $bundle;

            // Execute the search across the field's values.
            $items = [];
            foreach ($translation_entities as $translation_entity) {
              $translation_items = $this->searchEntityField($translation_entity, $field_name, $conditionVals['phpRegex'], $values['preceded'] ?? NULL);
              if (!empty($translation_items)) {
                $items = array_merge($items, $translation_items);
              }
            }

            // Handle situations with nested entities where the same field name
            // will show multiple times for the same root entity.
            if (!isset($results['results'][$id]['fields'][$field_name])) {
              $results['results'][$id]['fields'][$field_name] = [
                'results' => $items,
                'label' => $field_definition->getLabel(),
              ];
            }
            else {
              foreach ($items as $item) {
                $results['results'][$id]['fields'][$field_name]['results'][] = $item;
              }
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->getLogger('scanner')->error($e->getMessage());
      $this->messenger()->addError($this->t('An error occurred @e:', ['@e' => $e->getMessage()]));
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function replace(array $results_data, array $values, array $undo_data): array {
    // Determine the entity type from the plugin definition.
    $entity_type = $this->getPluginDefinition()['type'];

    $data = $undo_data;

    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      $is_revisionable = $entity_type_definition->isRevisionable();
      $entity_keys = $entity_type_definition->getKeys();
      $revision_key = $is_revisionable ? $entity_keys['revision'] : NULL;

      $conditionVals = $this->buildCondition($values['search'], $values['mode'], $values['wholeword'], $values['regex'], $values['preceded'], $values['followed']);

      // Get a list of the languages being tested.
      $languages = [];
      if ($values['language'] === 'all') {
        $languages = array_keys(AdminHelper::getAllEnabledLanguages());
      }
      else {
        $languages[] = $values['language'];
      }

      // Load the entities which match the criteria.
      $entities = $storage->loadMultiple(array_keys($results_data['results']));

      // Iterate over matched entities to extract information that will be
      // rendered in the results.
      foreach ($results_data['results'] as $id => $entity_data) {
        $entity = $entities[$id];

        // Track whether the entity was changed.
        $changed = FALSE;

        // Loop over each field.
        foreach (array_keys($entity_data['fields']) as $field_name) {
          $entity_field = $entity->get($field_name);
          $field_type = $entity_field->getFieldDefinition()->getType();

          // Loop over each supported language.
          foreach ($languages as $langcode) {
            // Load the entity and field for the requested language.
            if ($entity->hasTranslation($langcode)) {
              $entity = $entity->getTranslation($langcode);
              $entity_field = $entity->get($field_name);
            }

            // Loop over each value in the field.
            $new_value = [];
            foreach ($entity_field->getValue() as $delta => $field_value) {
              // Text fields have a specific structure, but only the 'value'
              // element needs to be processed.
              if (in_array($field_type, ['text_with_summary', 'text', 'text_long'])) {
                // Copy the whole structure, to make sure the other attributes
                // are retained.
                $new_value[$delta] = $field_value;

                // Process the 'value' element.
                $new_value[$delta]['value'] = preg_replace($conditionVals['phpRegex'], $values['replace'], $field_value['value']);

                // Process the 'summary' element on text_with_summary fields.
                if (isset($field_value['summary'])) {
                  $new_value[$delta]['summary'] = preg_replace($conditionVals['phpRegex'], $values['replace'], $field_value['summary']);
                }
              }

              // String fields have a single element, 'value'.
              elseif (in_array($field_type, ['string', 'string_long'])) {
                $new_value[$delta]['value'] = preg_replace($conditionVals['phpRegex'], $values['replace'], $field_value['value']);
              }

              // Link fields have multiple elements - 'uri', 'title', 'options',
              // so process each one.
              elseif ($field_type == 'link') {
                foreach ($field_value as $element_key => $element_value) {
                  // Only perform the replacement on string elements, skip
                  // anything else.
                  if (is_string($element_value)) {
                    $new_value[$delta][$element_key] = preg_replace($conditionVals['phpRegex'], $values['replace'], $element_value);
                  }
                  else {
                    $new_value[$delta][$element_key] = $element_value;
                  }
                }
              }
            }

            if ($new_value) {
              $entity->$field_name = $new_value;
              $changed = TRUE;
            }
          }
        }

        // Save the updated entity.
        if ($changed) {
          if ($is_revisionable) {
            if (!isset($data["$entity_type:$id"]['new_vid'])) {
              $data["$entity_type:$id"]['old_vid'] = $entity->{$revision_key}->getString();

              // Create a new revision to have the option of undoing it later
              // on.
              $entity->setNewRevision();
              $entity->revision_log = $this->t('Replaced %search with %replace via Scanner Search and Replace module.', [
                '%search' => $values['search'],
                '%replace' => $values['replace'],
              ]);

              // Not all entity types track the user who made this change,
              // e.g. it is available for nodes but not for paragraphs.
              if (method_exists($entity, 'setRevisionUserId')) {
                $entity->setRevisionUserId($this->currentUser->id());
                $entity->setRevisionCreationTime($this->time->getRequestTime());
              }
            }
          }

          $entity->save();

          // On revisionable entities log the new revision ID.
          if ($is_revisionable) {
            $data["$entity_type:$id"]['new_vid'] = $entity->{$revision_key}->getString();
          }
          // Otherwise just log the fact this entity was modified.
          else {
            $data["$entity_type:$id"][] = $id;
          }

          // Update the parent entity.
          $this->handleParentRelationship($entity, $values, $data);
        }
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      $this->getLogger('scanner')->error($e->getMessage());
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function undo(array $data): void {
    // Determine the entity type from the plugin definition.
    $entity_type = $this->getPluginDefinition()['type'];

    try {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $revision = $storage->loadRevision($data['old_vid']);
      $revision->setNewRevision();
      $revision->setRevisionLogMessage($this->t('Copy of the revision from %date via Search and Replace Undo', [
        '%date' => $this->dateFormatter->format($revision->getRevisionCreationTime()),
      ]));
      $revision->isDefaultRevision(TRUE);
      $revision->setRevisionUserId($this->currentUser->id());
      $revision->setRevisionCreationTime($this->time->getRequestTime());
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
    // Most entity types don't need to do anything here.
  }

  /**
   * Helper function to "build" the proper query condition.
   *
   * @param string $search
   *   The string that is to be searched for.
   * @param bool $mode
   *   The boolean that indicated whether the search should be case-sensitive.
   * @param bool $wholeword
   *   The boolean that indicates whether the search should be word-bounded.
   * @param bool $regex
   *   The boolean that indicates whether the search term is a regular
   *   expression.
   * @param string $preceded
   *   The string for preceded expression.
   * @param string $followed
   *   The string for the succeeding expression.
   *
   * @return array
   *   Returns an associative array keyed by:
   *   - condition: The target ICU regular expression (for database use);
   *   - phpRegex: The target perl-compatible regular expression (for PHP);
   *   - operator: The operator to use in a database condition; usually one of
   *     'REGEXP', 'LIKE', 'REGEXP_LIKE'.
   */
  protected function buildCondition(string $search, bool $mode, bool $wholeword, bool $regex, string $preceded, string $followed): array {
    $preceded_php = '';
    if (!empty($preceded)) {
      if (!$regex) {
        $preceded = addcslashes($preceded, $this->scannerRegexChars);
      }
      $preceded_php = '(?<=' . $preceded . ')';
    }
    $followed_php = '';
    if (!empty($followed)) {
      if (!$regex) {
        $followed = addcslashes($followed, $this->scannerRegexChars);
      }
      $followed_php = '(?=' . $followed . ')';
    }

    $word_boundaries_helper = $this->scannerHelper;
    if ($word_boundaries_helper->whichToUse() === 'icu') {
      $word_boundary_prefix = $word_boundary_suffix = "\\b";
    }
    else {
      // Control which word boundaries are used, used for compatibility with
      // different releases of MySQL.
      // @see https://dev.mysql.com/doc/refman/8.0/en/regexp.html#regexp-compatibility
      // @see https://mariadb.com/kb/en/regular-expressions-overview/#word-boundaries
      $word_boundary_prefix = '[[:<:]]';
      $word_boundary_suffix = '[[:>:]]';
    }

    // Case 1.
    if ($wholeword && $regex) {
      $value = $word_boundary_prefix . $preceded . $search . $followed . $word_boundary_suffix;
      $operator = 'REGEXP';
      $phpRegex = '/\b' . $preceded_php . $search . $followed_php . '\b/';
    }
    // Case 2.
    elseif ($wholeword && !$regex) {
      $value = $word_boundary_prefix . $preceded . addcslashes($search, $this->scannerRegexChars) . $followed . $word_boundary_suffix;
      $operator = 'REGEXP';
      $phpRegex = '/\b' . $preceded_php . addcslashes($search, $this->scannerRegexChars) . $followed . '\b/';
    }
    // Case 3.
    elseif (!$wholeword && $regex) {
      $value = $preceded . $search . $followed;
      $operator = 'REGEXP';
      $phpRegex = '/' . $preceded_php . $search . $followed_php . '/';
    }
    // Case 4.
    else {
      $value = '%' . $preceded . addcslashes($search, $this->scannerRegexChars) . $followed . '%';
      $operator = 'LIKE';
      $phpRegex = '/' . $preceded . addcslashes($search, $this->scannerRegexChars) . $followed . '/';
    }

    if ($mode) {
      if ($operator === 'REGEXP' && $word_boundaries_helper->supportsInlinePcreFlags()) {
        // Check if the database engine supports the inline PCRE regex. If it
        // does, then we'll use that to specify the case-sensitivity rather than
        // the BINARY flag.
        // https://mariadb.com/kb/en/pcre/#option-setting
        $value = $word_boundaries_helper->getCaseSensitiveRegexPrefix() . $value;
      }
      elseif ($operator === 'REGEXP' && $word_boundaries_helper->shouldUseRegexpLike()) {
        // Check if it supports the "REGEXP BINARY" syntax. If it does not, then
        // we'll use REGEXP_LIKE instead.
        $operator = 'REGEXP_LIKE';
      }
      else {
        $operator .= ' BINARY';
      }
    }
    else {
      $phpRegex .= 'i';
    }

    return [
      'condition' => $value,
      'phpRegex' => $phpRegex,
      'operator' => $operator,
    ];
  }

  /**
   * Ensures the query is limited by the specified search conditions.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to modify.
   * @param array $condition
   *   An array of condition values as returned by ::buildCondition().
   * @param string $entity_type
   *   The entity type that is being searched.
   * @param string $fieldname
   *   The name of the entity field having a condition added.
   * @param bool $mode
   *   TRUE for a case-sensitive search; FALSE otherwise.
   * @param string $language
   *   The language code or 'all' for all languages.
   */
  protected function addQueryCondition(QueryInterface $query, array $condition, string $entity_type, string $fieldname, bool $mode, string $language): void {
    // If the request is to query all languages then ignore the langcode value.
    if ($language === 'all') {
      $langcode = NULL;
    }
    // When querying a specific language, add that to the query.
    else {
      $langcode = $language;
      $query->condition('langcode', $language, '=');
    }

    if ($condition['operator'] === 'REGEXP_LIKE') {
      // The REGEXP_LIKE() function can't be added directly to the entity
      // query, so add a tag and then add the actual condition later.
      /* @see \scanner_query_scanner_search_regexp_like_alter() */
      /* @see \Drupal\scanner\Plugin\Scanner\Entity::addRegexpLikeCondition() */
      $query->addTag('scanner_search_regexp_like')
        ->addMetaData('scanner_search_regexp_like', [
          'entity_type_id' => $query->getEntityTypeId(),
          'fieldname' => $fieldname,
          'langcode' => $langcode,
          'mode' => $mode,
          'pattern' => $condition['condition'],
        ]);
    }
    else {
      // Work out what sort of query to do. Some field types require multiple
      // queries, so add them as separate query conditions.
      $fieldDefinition = $this->entityFieldManager->getFieldStorageDefinitions($entity_type)[$fieldname];
      if ($fieldDefinition->getType() === 'text_with_summary') {
        $group = $query->orConditionGroup()
          ->condition("$fieldname.value", $condition['condition'], $condition['operator'], $langcode)
          ->condition("$fieldname.summary", $condition['condition'], $condition['operator'], $langcode);
        $query->condition($group);
      }
      else {
        $query->condition($fieldname, $condition['condition'], $condition['operator'], $langcode);
      }
    }
  }

  /**
   * Search fields on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to search.
   * @param string $field_name
   *   A field to check.
   * @param string $regex
   *   The search string.
   * @param string|null $preceded
   *   The 'preceded' string. Optional.
   *
   * @return array
   *   The list of matches.
   */
  protected function searchEntityField(EntityInterface $entity, string $field_name, string $regex, ?string $preceded = NULL): array {
    $return = [];

    // Get details from the field specifications.
    $entity_field = $entity->get($field_name);
    $field_type = $entity_field->getFieldDefinition()->getType();
    $field_value = $entity_field->getValue();

    // Loop over each field value; supports multi-value fields.
    foreach ($field_value as $item) {
      $matches = NULL;

      // Different versions of long text fields. These are expected to have a
      // 'value' element, and will normally also have a 'format' element.
      // Text-with-summary fields will also have a 'summary' value.
      if (in_array($field_type, ['text_with_summary', 'text', 'text_long'])) {
        // Support the 'text_with_summary' field's summary element and the
        // expected 'value' element.
        foreach (['value', 'summary'] as $field_key) {
          // Don't assume the element is defined, e.g. only text_with_summary
          // fields have a 'summary' element.
          if (!isset($item[$field_key])) {
            continue;
          }

          // Find all instances of the term search string.
          preg_match_all($regex, $item[$field_key], $matches, PREG_OFFSET_CAPTURE);

          // Build an array of strings which are displayed in the results.
          foreach ($matches[0] as $v) {
            // The offset of the matched term(s) in the field's text.
            $start = $v[1];
            if (!is_null($preceded) && $preceded !== '') {
              // Bolding won't work if starting position is in the middle of a
              // word (non-word bounded searches), therefore move the start
              // position back as many character as there are in the
              // 'preceded' text.
              $start -= strlen($preceded);
            }

            // Extract part of the text which include the search term plus six
            // "words" following it. After finding the string, bold the search
            // term.
            $replaced = preg_replace($regex, "<strong>$v[0]</strong>", preg_split("/\s+/", substr($item[$field_key], $start), 6));
            if (count($replaced) > 1) {
              // The final index contains the remainder of the text, which is
              // irrelevant, so discard it.
              array_pop($replaced);
            }

            $return[] = implode(' ', $replaced);
          }
        }
      }

      // Link fields have a specific format:
      // - 'uri' - The URI/URL for this link.
      // - 'title' - The string used for displaying the link.
      // @todo Search the URL alias.
      elseif ($field_type == 'link') {
        foreach (['uri', 'title'] as $key) {
          if (!empty($item[$key])) {
            preg_match($regex, $item[$key], $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches[0][0])) {
              $match = $matches[0][0];
              $return[] = preg_replace($regex, "<strong>$match</strong>", $item[$key]);
            }
          }
        }
      }

      // String fields have a 'value' element.
      elseif (in_array($field_type, ['string', 'string_long'])) {
        preg_match($regex, $item['value'], $matches, PREG_OFFSET_CAPTURE);
        if (!empty($matches[0][0])) {
          $match = $matches[0][0];
          $return[] = preg_replace($regex, "<strong>$match</strong>", $item['value']);
        }
      }
    }

    return $return;
  }

}
