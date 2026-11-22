<?php

declare(strict_types=1);

namespace Drupal\jyc_api;

use Drupal\Component\Utility\UrlHelper;
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
 * Imports "Courses" nodes (English + Arabic) with their photos from an
 * archive with a courses.json and an images folder:
 *
 *   {
 *     "format": "jyc-courses", "version": 1,
 *     "courses": [{
 *       "bookingLink": "https://…",                        -> field_booking_link
 *       "image": { "file": "images/x.jpg", "alt": "…" },   -> field_main_image
 *       "en": { "title": "…", "text": "<p>…</p>" },        -> title, field_content
 *       "ar": { "title": "…", "text": "<p>…</p>" }         (optional translation)
 *     }]
 *   }
 *
 * Courses keep the order of the file ("authored on" one minute apart). A
 * course with the same title (default language) is updated instead of
 * created again, so importing the same archive twice is safe.
 */
final class CourseImporter {

  private const IMAGE_DIRECTORY = 'public://courses';

  private const TEXT_FORMAT = 'full_html';

  private const MAX_COURSES = 500;

  /** "Authored on" of the first course; the next ones follow one minute apart. */
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
   * Imports the courses of an opened archive (courses.json already read).
   *
   * @return array{created: int, updated: int, images: int, errors: string[]}
   *
   * @throws \RuntimeException
   */
  public function importFrom(ImportArchive $archive, array $manifest): array {
    if (($manifest['format'] ?? '') !== 'jyc-courses' || !is_array($manifest['courses'] ?? NULL)) {
      throw new \RuntimeException('courses.json is not a JYC courses file (expected "format": "jyc-courses" and a "courses" list).');
    }
    $this->result = ['created' => 0, 'updated' => 0, 'images' => 0, 'errors' => []];
    $media = new MediaSaver($this->entityTypeManager, $this->fileSystem, $this->fileRepository, $this->currentUser);
    $first = (new DrupalDateTime(self::FIRST_CREATED))->getTimestamp();

    foreach (array_slice($manifest['courses'], 0, self::MAX_COURSES) as $index => $course) {
      try {
        $this->importCourse(is_array($course) ? $course : [], $archive, $media, $first + $index * 60);
      }
      catch (\Throwable $e) {
        $title = is_array($course) ? (string) ($course['en']['title'] ?? '') : '';
        $this->result['errors'][] = '#' . ($index + 1) . ($title !== '' ? " ($title)" : '') . ': ' . $e->getMessage();
      }
    }
    $this->result['images'] = $media->saved;
    return $this->result;
  }

  private function importCourse(array $course, ImportArchive $archive, MediaSaver $media, int $created): void {
    $default = $this->languageManager->getDefaultLanguage()->getId();
    $texts = $this->texts($course[$default] ?? $course['en'] ?? NULL);
    if ($texts === NULL) {
      throw new \RuntimeException("A title and a text in the default language ($default) are required.");
    }
    $link = trim((string) ($course['bookingLink'] ?? ''));
    if ($link === '' || !UrlHelper::isValid($link, TRUE)) {
      throw new \RuntimeException('"bookingLink" must be a full web address (https://…).');
    }
    if (empty($course['image'])) {
      throw new \RuntimeException('"image" is required (the Courses content type needs a main image).');
    }
    $published = ($course['published'] ?? TRUE) !== FALSE;

    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'courses')
      ->condition('title', $texts['title'])
      ->condition('langcode', $default)
      ->range(0, 1)
      ->execute();
    $node = $ids ? $storage->load(reset($ids)) : NULL;
    $isNew = !$node instanceof NodeInterface;
    if ($isNew) {
      $node = $storage->create(['type' => 'courses', 'langcode' => $default, 'uid' => $this->currentUser->id()]);
    }
    elseif ($node->hasTranslation($default)) {
      $node = $node->getTranslation($default);
    }

    // The photo first: a broken image stops this course before anything is saved.
    $imageId = $media->save($archive, (array) $course['image'], self::IMAGE_DIRECTORY);

    $this->applyTexts($node, $texts, $created, $published);
    $node->set('field_main_image', ['target_id' => $imageId]);
    $node->set('field_booking_link', mb_substr($link, 0, 255));

    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if ($langcode === $default || ($other = $this->texts($course[$langcode] ?? NULL)) === NULL) {
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
      $node->setRevisionLogMessage('Imported from a courses archive.');
    }
    $node->save();
    $this->result[$isNew ? 'created' : 'updated']++;
  }

  /** Title and text of one language, or NULL without both. */
  private function texts(mixed $texts): ?array {
    if (!is_array($texts) || trim((string) ($texts['title'] ?? '')) === '' || trim((string) ($texts['text'] ?? '')) === '') {
      return NULL;
    }
    return [
      'title' => mb_substr(trim((string) $texts['title']), 0, 255),
      // Trusted editors only, but scripts are still removed.
      'text' => Xss::filterAdmin(trim((string) $texts['text'])),
    ];
  }

  private function applyTexts(NodeInterface $node, array $texts, int $created, bool $published): void {
    $node->setTitle($texts['title']);
    $node->set('field_content', ['value' => $texts['text'], 'format' => self::TEXT_FORMAT]);
    $node->setCreatedTime($created);
    $node->setPublished($published);
  }

}
