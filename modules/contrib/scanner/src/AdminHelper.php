<?php

namespace Drupal\scanner;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Shared logic for use in the module.
 */
class AdminHelper {

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
    return [
      '#theme' => 'scanner_results',
      '#title' => t('Search results'),
      '#counts' => self::buildResultsCounts($results),
      '#results' => $results,
    ];
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
