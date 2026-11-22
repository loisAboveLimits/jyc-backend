<?php

declare(strict_types=1);

namespace Drupal\jyc_api;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports "Testimonies" nodes (English + Arabic) from an archive with a
 * testimonies.json:
 *
 *   {
 *     "format": "jyc-testimonies", "version": 1,
 *     "testimonies": [{
 *       "rating": 5,                                     -> field_ratings (1–5)
 *       "en": { "name": "…", "title": "…", "text": "…" },   -> title, field_content summary, field_content
 *       "ar": { "name": "…", "title": "…", "text": "…" }    (optional translation)
 *     }]
 *   }
 *
 * The reviewer's name is the node title; the review's headline is the
 * summary of field_content; the review itself is field_content (plain text).
 * Items keep the order of the file ("authored on" one minute apart). An item
 * with the same name and headline (default language) is updated instead of
 * created again, so importing the same archive twice is safe.
 */
final class TestimonyImporter {

  private const TEXT_FORMAT = 'plain_text';

  private const MAX_ITEMS = 500;

  /** "Authored on" of the first item; the next ones follow one minute apart. */
  private const FIRST_CREATED = '2026-01-01 09:00:00';

  private array $result = [];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly AccountInterface $currentUser,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Imports the items of an opened archive (testimonies.json already read).
   *
   * @return array{created: int, updated: int, errors: string[]}
   *
   * @throws \RuntimeException
   */
  public function importFrom(ImportArchive $archive, array $manifest): array {
    if (($manifest['format'] ?? '') !== 'jyc-testimonies' || !is_array($manifest['testimonies'] ?? NULL)) {
      throw new \RuntimeException('testimonies.json is not a JYC testimonies file (expected "format": "jyc-testimonies" and a "testimonies" list).');
    }
    $this->result = ['created' => 0, 'updated' => 0, 'errors' => []];
    $first = (new DrupalDateTime(self::FIRST_CREATED))->getTimestamp();

    foreach (array_slice($manifest['testimonies'], 0, self::MAX_ITEMS) as $index => $item) {
      try {
        $this->importItem(is_array($item) ? $item : [], $first + $index * 60);
      }
      catch (\Throwable $e) {
        $name = is_array($item) ? (string) ($item['en']['name'] ?? '') : '';
        $this->result['errors'][] = '#' . ($index + 1) . ($name !== '' ? " ($name)" : '') . ': ' . $e->getMessage();
      }
    }
    return $this->result;
  }

  private function importItem(array $item, int $created): void {
    $default = $this->languageManager->getDefaultLanguage()->getId();
    $texts = $this->texts($item[$default] ?? $item['en'] ?? NULL);
    if ($texts === NULL) {
      throw new \RuntimeException("A name and a review text in the default language ($default) are required.");
    }
    $rating = (int) ($item['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) {
      throw new \RuntimeException('"rating" must be a whole number from 1 to 5.');
    }
    $published = ($item['published'] ?? TRUE) !== FALSE;

    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'testimonies')
      ->condition('title', $texts['name'])
      ->condition('langcode', $default);
    $texts['title'] === '' ? $query->notExists('field_content.summary') : $query->condition('field_content.summary', $texts['title']);
    $ids = $query->range(0, 1)->execute();
    $node = $ids ? $storage->load(reset($ids)) : NULL;
    $isNew = !$node instanceof NodeInterface;
    if ($isNew) {
      $node = $storage->create(['type' => 'testimonies', 'langcode' => $default, 'uid' => $this->currentUser->id()]);
    }
    elseif ($node->hasTranslation($default)) {
      $node = $node->getTranslation($default);
    }

    $this->applyTexts($node, $texts, $created, $published);
    $node->set('field_ratings', $rating);

    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if ($langcode === $default || ($other = $this->texts($item[$langcode] ?? NULL)) === NULL) {
        continue;
      }
      $translation = $node->hasTranslation($langcode) ? $node->getTranslation($langcode) : $node->addTranslation($langcode);
      $this->applyTexts($translation, $other, $created, $published);
      if ($translation->hasField('content_translation_source') && $translation->get('content_translation_source')->isEmpty()) {
        $translation->set('content_translation_source', $default);
      }
    }

    if ($node->getEntityType()->isRevisionable()) {
      $node->setNewRevision();
      $node->setRevisionUserId($this->currentUser->id());
      $node->setRevisionLogMessage('Imported from a testimonies archive.');
    }
    $node->save();
    $this->result[$isNew ? 'created' : 'updated']++;
  }

  /** Name, headline and text of one language, or NULL without a name and text. */
  private function texts(mixed $texts): ?array {
    if (!is_array($texts) || trim((string) ($texts['name'] ?? '')) === '' || trim((string) ($texts['text'] ?? '')) === '') {
      return NULL;
    }
    // Plain text field: any HTML is removed.
    $plain = fn(string $key) => trim(strip_tags((string) ($texts[$key] ?? '')));
    return [
      'name' => mb_substr($plain('name'), 0, 255),
      'title' => mb_substr($plain('title'), 0, 255),
      'text' => $plain('text'),
    ];
  }

  private function applyTexts(NodeInterface $node, array $texts, int $created, bool $published): void {
    $node->setTitle($texts['name']);
    $node->set('field_content', ['value' => $texts['text'], 'summary' => $texts['title'], 'format' => self::TEXT_FORMAT]);
    $node->setCreatedTime($created);
    $node->setPublished($published);
  }

}
