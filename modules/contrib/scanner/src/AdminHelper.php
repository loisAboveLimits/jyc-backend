<?php

namespace Drupal\scanner;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Shared logic for use in the module.
 */
class AdminHelper {

  /**
   * The number of entities above which the results are collapsed.
   *
   * @see ::buildResultsGroups()
   */
  const COLLAPSE_RESULTS_LIMIT = 20;

  /**
   * Show a message on the screen.
   */
  public static function addMessage($message): void {
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Show an error message on the screen.
   */
  public static function addError($message): void {
    \Drupal::messenger()->addError($message);
  }

  /**
   * Log or show a notice message on the screen.
   */
  public static function addToLog($message, $debug = FALSE): void {
    if ($debug) {
      \Drupal::logger('scanner')->notice($message);
    }
  }

  /**
   * Get all enabled languages, excluding current language.
   */
  public static function getOtherEnabledLanguages(): array {
    // Get the list of all languages.
    $default_langcode = self::getDefaultLangcode();
    $other_languages = [];

    // Add each enabled language, aside from the current language to an array.
    foreach (self::getAllEnabledLanguages() as $field_language_code => $field_language) {
      if ($field_language_code != $default_langcode) {
        $other_languages[$field_language_code] = $field_language;
      }
    }
    return $other_languages;
  }

  /**
   * Get current language.
   */
  public static function getDefaultLangcode(): string {
    $language = \Drupal::languageManager()->getDefaultLanguage();
    return $language->getId();
  }

  /**
   * Get all enabled languages, including the current language.
   */
  public static function getAllEnabledLanguages(): array {
    // Get the list of all languages.
    $all_languages = \Drupal::languageManager()->getLanguages();
    $other_languages = [];

    // Add each enabled language, aside from the current language to an array.
    foreach ($all_languages as $field_language_code => $field_language) {
      $other_languages[$field_language_code] = $field_language->getName();
    }
    return $other_languages;
  }

  /**
   * Get the latest revision.
   */
  public static function latestRevision($nid, &$vid, $langcode) {
    // Change record below might be helpful for future improvements.
    // @see https://www.drupal.org/node/2942013
    $lang = self::getDefaultLangcode();
    if (!isset($langcode)) {
      $langcode = $lang;
    }
    if ($lang != $langcode) {
      $lang = $langcode;
    }
    $latestRevisionResult = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->latestRevision()
      ->condition('nid', $nid, '=')
      ->execute();
    if (count($latestRevisionResult)) {
      $node_revision_id = key($latestRevisionResult);
      if ($node_revision_id == $vid) {
        // There is no pending revision, the current revision is the latest.
        return FALSE;
      }
      $vid = $node_revision_id;
      $latestRevision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($node_revision_id);
      if ($latestRevision->language()->getId() != $lang && $latestRevision->hasTranslation($lang)) {
        $latestRevision = $latestRevision->getTranslation($lang);
      }
      return $latestRevision;
    }
    return FALSE;
  }

  /**
   * Prepares a new revision of a given entity, if applicable.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   A revision log message to set.
   * @param int $current_uid
   *   The user ID of the current logged-in user.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The moderation state for the given entity.
   */
  public static function prepareNewRevision(EntityInterface $entity, string|TranslatableMarkup $message, int $current_uid): EntityInterface|RevisionLogInterface {
    $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
    if ($storage instanceof ContentEntityStorageInterface) {
      $revision = $storage->createRevision($entity);
      if ($revision instanceof RevisionLogInterface) {
        $revision->setRevisionLogMessage($message);
        $revision->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        $revision->setRevisionUserId($current_uid);
      }
      return $revision;
    }
    return $entity;
  }

  /**
   * Build the results output.
   *
   * @param array $results
   *   The results structure from the search query.
   *
   * @return array
   *   Renderable output.
   */
  public static function buildResults(array $results) {
    $counts = self::buildResultsCounts($results);
    return [
      '#theme' => 'scanner_results',
      '#title' => t('Search results'),
      '#counts' => $counts,
      '#results' => $results,
      '#groups' => self::buildResultsGroups($results, $counts['entities'] > self::COLLAPSE_RESULTS_LIMIT),
    ];
  }

  /**
   * Build a collapsible group of results for each entity type.
   *
   * @param array $results
   *   The results structure from the search query.
   * @param bool $collapsed
   *   (optional) Whether the groups are closed. Closing them for large result
   *   sets makes it easier to see which entity types were matched. Defaults
   *   to FALSE.
   *
   * @return array
   *   Renderable output, keyed by entity type.
   */
  public static function buildResultsGroups(array $results, bool $collapsed = FALSE): array {
    $groups = [];
    foreach ($results as $entity_type => $data) {
      $counts = self::buildResultsCounts([$entity_type => $data]);

      // Matches are excerpts of the field values, so they can contain tags
      // that aren't closed. Close them, so that they don't affect the rest of
      // the results.
      foreach ($data['results'] as &$entity_data) {
        foreach ($entity_data['fields'] as &$field_data) {
          foreach ($field_data['results'] as &$result) {
            $result = Html::normalize(strip_tags($result, '<strong>'));
          }
        }
      }
      unset($entity_data, $field_data, $result);

      $groups[$entity_type] = [
        '#type' => 'details',
        '#title' => t('@label (@entities, @matches)', [
          '@label' => $data['label'] ?? $entity_type,
          '@entities' => \Drupal::translation()->formatPlural($counts['entities'], '1 entity', '@count entities'),
          '@matches' => \Drupal::translation()->formatPlural($counts['matches'], '1 match', '@count matches'),
        ]),
        '#open' => !$collapsed,
        'results' => [
          '#theme' => 'scanner_results_group',
          '#results' => $data['results'],
        ],
      ];
    }
    ksort($groups);
    return $groups;
  }

  /**
   * Generate the counts for results.
   *
   * @param array $results
   *   The results from the search operation.
   *
   * @return array
   *   An array containing the number of 'entities' and number of 'matches.
   */
  public static function buildResultsCounts(array $results) {
    $counts = [
      'entities' => 0,
      'matches' => 0,
    ];
    foreach ($results as $data) {
      $counts['entities'] += count($data['results']);

      foreach ($data['results'] as $entity_data) {
        foreach ($entity_data['fields'] as $field_data) {
          $counts['matches'] += count($field_data['results']);
        }
      }
    }

    return $counts;
  }

}
