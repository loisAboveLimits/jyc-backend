<?php

declare(strict_types=1);

namespace Drupal\jyc_api;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports "Teams" nodes (English + Arabic) with their photos from an archive
 * with a teams.json and an images folder:
 *
 *   {
 *     "format": "jyc-teams", "version": 1,
 *     "members": [{
 *       "image": { "file": "images/x.jpg", "alt": "…" },   -> field_main_image
 *       "en": { "name": "…", "position": "…", "bio": "<p>…</p>" },  -> title, field_position, field_content
 *       "ar": { "name": "…", "position": "…", "bio": "<p>…</p>" }   (optional translation)
 *     }]
 *   }
 *
 * Members keep the order of the file: their "authored on" dates go up one
 * minute per member, so sorting by date (oldest first) gives the same order.
 * A member with the same name (in the default language) is updated instead
 * of created again, so importing the same archive twice is safe.
 */
final class TeamImporter {

  private const IMAGE_DIRECTORY = 'public://team';

  private const TEXT_FORMAT = 'full_html';

  private const MAX_MEMBERS = 500;

  /** "Authored on" of the first member; the next ones follow one minute apart. */
  private const FIRST_CREATED = '2026-01-01 09:00:00';

  private array $result = [];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileSystemInterface $fileSystem,
    private readonly FileRepositoryInterface $fileRepository,
    private readonly LanguageManagerInterface $languageManager,
    private readonly AccountInterface $currentUser,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('language_manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Imports the members of an opened archive (teams.json already read).
   *
   * @return array{created: int, updated: int, images: int, errors: string[]}
   *
   * @throws \RuntimeException
   */
  public function importFrom(ImportArchive $archive, array $manifest): array {
    if (($manifest['format'] ?? '') !== 'jyc-teams' || !is_array($manifest['members'] ?? NULL)) {
      throw new \RuntimeException('teams.json is not a JYC team file (expected "format": "jyc-teams" and a "members" list).');
    }
    $this->result = ['created' => 0, 'updated' => 0, 'images' => 0, 'errors' => []];
    $media = new MediaSaver($this->entityTypeManager, $this->fileSystem, $this->fileRepository, $this->currentUser);
    $first = (new DrupalDateTime(self::FIRST_CREATED))->getTimestamp();

    foreach (array_slice($manifest['members'], 0, self::MAX_MEMBERS) as $index => $member) {
      try {
        $this->importMember(is_array($member) ? $member : [], $archive, $media, $first + $index * 60);
      }
      catch (\Throwable $e) {
        $name = is_array($member) ? (string) ($member['en']['name'] ?? '') : '';
        $this->result['errors'][] = '#' . ($index + 1) . ($name !== '' ? " ($name)" : '') . ': ' . $e->getMessage();
      }
    }
    $this->result['images'] = $media->saved;
    return $this->result;
  }

  private function importMember(array $member, ImportArchive $archive, MediaSaver $media, int $created): void {
    $default = $this->languageManager->getDefaultLanguage()->getId();
    $texts = $this->texts($member[$default] ?? $member['en'] ?? NULL);
    if ($texts === NULL) {
      throw new \RuntimeException("A name and a position in the default language ($default) are required.");
    }
    $published = ($member['published'] ?? TRUE) !== FALSE;

    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'teams')
      ->condition('title', $texts['name'])
      ->condition('langcode', $default)
      ->range(0, 1)
      ->execute();
    $node = $ids ? $storage->load(reset($ids)) : NULL;
    $isNew = !$node instanceof NodeInterface;
    if ($isNew) {
      $node = $storage->create(['type' => 'teams', 'langcode' => $default, 'uid' => $this->currentUser->id()]);
    }
    elseif ($node->hasTranslation($default)) {
      $node = $node->getTranslation($default);
    }

    // The photo first: a broken image stops this member before anything is saved.
    $imageId = !empty($member['image']) ? $media->save($archive, (array) $member['image'], self::IMAGE_DIRECTORY) : NULL;

    $this->applyTexts($node, $texts, $created, $published);
    $node->set('field_main_image', $imageId === NULL ? [] : ['target_id' => $imageId]);

    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if ($langcode === $default || ($other = $this->texts($member[$langcode] ?? NULL)) === NULL) {
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
      $node->setRevisionLogMessage('Imported from a team archive.');
    }
    $node->save();
    $this->result[$isNew ? 'created' : 'updated']++;
  }

  /** Name, position and bio of one language, or NULL without a name and position. */
  private function texts(mixed $texts): ?array {
    if (!is_array($texts) || trim((string) ($texts['name'] ?? '')) === '' || trim((string) ($texts['position'] ?? '')) === '') {
      return NULL;
    }
    return [
      'name' => mb_substr(trim((string) $texts['name']), 0, 255),
      'position' => mb_substr(trim((string) $texts['position']), 0, 255),
      // Trusted editors only, but scripts are still removed.
      'bio' => Xss::filterAdmin(trim((string) ($texts['bio'] ?? ''))),
    ];
  }

  private function applyTexts(NodeInterface $node, array $texts, int $created, bool $published): void {
    $node->setTitle($texts['name']);
    $node->set('field_position', $texts['position']);
    $node->set('field_content', $texts['bio'] === '' ? [] : ['value' => $texts['bio'], 'format' => self::TEXT_FORMAT]);
    $node->setCreatedTime($created);
    $node->setPublished($published);
  }

}
