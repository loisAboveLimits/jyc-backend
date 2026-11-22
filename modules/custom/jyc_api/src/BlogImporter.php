<?php

declare(strict_types=1);

namespace Drupal\jyc_api;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports "Blogs" posts (English + Arabic) with their images from a
 * tar.gz / tgz / tar.bz2 archive.
 *
 * The archive holds a posts.json (at the top or inside one folder) and the
 * images it refers to:
 *
 *   jyc-blog-posts/
 *     posts.json
 *     images/red-sea-sunset.jpg …
 *
 *   posts.json:
 *   {
 *     "format": "jyc-blog-posts", "version": 1,
 *     "posts": [{
 *       "slug": "red-sea-sunset",          -> URL /blogs/red-sea-sunset (both languages)
 *       "date": "2026-11-10",              -> authored on
 *       "published": true,
 *       "image": { "file": "images/x.jpg", "alt": "…" },        -> field_main_image
 *       "gallery": [{ "file": "images/y.jpg", "alt": "…" }],    -> field_gallery
 *       "en": { "title": "…", "summary": "…", "body": "<p>…</p>" },
 *       "ar": { "title": "…", "summary": "…", "body": "<p>…</p>" }  (optional)
 *     }]
 *   }
 *
 * A post whose URL already exists is updated instead of created again, so
 * importing the same archive twice is safe. Images become Media (image)
 * items in public://blogs; an image already imported is reused.
 *
 * Nothing is unpacked to disk: each file is read from the archive directly.
 */
final class BlogImporter {

  /** Where the images are stored. */
  private const IMAGE_DIRECTORY = 'public://blogs';

  /** Text format of field_content (the only one allowed for Blogs). */
  private const TEXT_FORMAT = 'full_html';

  private const MAX_POSTS = 500;

  private ?ImportArchive $archive = NULL;

  private ?MediaSaver $mediaSaver = NULL;

  private array $result = [];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileSystemInterface $fileSystem,
    private readonly FileRepositoryInterface $fileRepository,
    private readonly LanguageManagerInterface $languageManager,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly AccountInterface $currentUser,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
    );
  }

  /**
   * Imports an archive.
   *
   * @return array{created: int, updated: int, images: int, posts: string[], errors: string[]}
   *   What was done; one error message per post that could not be imported.
   *
   * @throws \RuntimeException
   *   When the archive itself can't be read (wrong type, no posts.json …).
   */
  public function import(string $archivePath): array {
    $archive = ImportArchive::open($archivePath);
    $found = $archive->manifest('posts.json');
    if ($found === NULL) {
      throw new \RuntimeException('posts.json was not found in the archive.');
    }
    return $this->importFrom($archive, $found[1]);
  }

  /**
   * Imports the posts of an opened archive (posts.json already read).
   *
   * @throws \RuntimeException
   */
  public function importFrom(ImportArchive $archive, array $manifest): array {
    if (($manifest['format'] ?? '') !== 'jyc-blog-posts' || !is_array($manifest['posts'] ?? NULL)) {
      throw new \RuntimeException('posts.json is not a JYC blog posts file (expected "format": "jyc-blog-posts" and a "posts" list).');
    }
    $this->result = ['created' => 0, 'updated' => 0, 'images' => 0, 'posts' => [], 'errors' => []];
    $this->mediaSaver = new MediaSaver($this->entityTypeManager, $this->fileSystem, $this->fileRepository, $this->currentUser);
    $this->archive = $archive;

    foreach (array_slice($manifest['posts'], 0, self::MAX_POSTS) as $index => $post) {
      $label = is_array($post) && is_string($post['slug'] ?? NULL) ? $post['slug'] : '#' . ($index + 1);
      try {
        $this->importPost(is_array($post) ? $post : []);
      }
      catch (\Throwable $e) {
        $this->result['errors'][] = "$label: " . $e->getMessage();
      }
    }

    $this->result['images'] = $this->mediaSaver->saved;
    $this->archive = NULL;
    return $this->result;
  }

  private function importPost(array $post): void {
    $slug = (string) ($post['slug'] ?? '');
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
      throw new \RuntimeException('"slug" must use lowercase letters, numbers and dashes only.');
    }
    $default = $this->languageManager->getDefaultLanguage()->getId();
    $texts = $this->texts($post[$default] ?? $post['en'] ?? NULL);
    if ($texts === NULL) {
      throw new \RuntimeException("A title in the default language ($default) is required.");
    }

    $alias = "/blogs/$slug";
    $created = $this->timestamp((string) ($post['date'] ?? ''));
    $published = ($post['published'] ?? TRUE) !== FALSE;

    $node = $this->findByAlias($alias);
    $isNew = $node === NULL;
    if ($isNew) {
      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'blogs',
        'langcode' => $default,
        'uid' => $this->currentUser->id(),
      ]);
    }
    elseif ($node->hasTranslation($default)) {
      $node = $node->getTranslation($default);
    }

    // Images first: a broken image stops this post before anything is saved.
    $main = !empty($post['image']) ? $this->media($post['image']) : NULL;
    $gallery = [];
    foreach (array_slice((array) ($post['gallery'] ?? []), 0, 50) as $image) {
      $gallery[] = ['target_id' => $this->media((array) $image)];
    }

    $this->applyTexts($node, $texts, $alias, $created, $published);
    $node->set('field_main_image', $main === NULL ? [] : ['target_id' => $main]);
    $node->set('field_gallery', $gallery);

    // Other languages (e.g. Arabic) as translations of the same post.
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if ($langcode === $default || ($other = $this->texts($post[$langcode] ?? NULL)) === NULL) {
        continue;
      }
      $translation = $node->hasTranslation($langcode) ? $node->getTranslation($langcode) : $node->addTranslation($langcode);
      $this->applyTexts($translation, $other, $alias, $created, $published);
      if ($translation->hasField('content_translation_source') && $translation->get('content_translation_source')->isEmpty()) {
        $translation->set('content_translation_source', $default);
      }
    }

    if ($node->getEntityType()->isRevisionable()) {
      $node->setNewRevision();
      $node->setRevisionUserId($this->currentUser->id());
      $node->setRevisionLogMessage('Imported from a blog posts archive.');
    }
    $node->save();

    $this->result[$isNew ? 'created' : 'updated']++;
    $this->result['posts'][] = $alias;
  }

  /** Title, summary and body of one language, or NULL when there is no title. */
  private function texts(mixed $texts): ?array {
    if (!is_array($texts) || trim((string) ($texts['title'] ?? '')) === '') {
      return NULL;
    }
    return [
      'title' => mb_substr(trim((string) $texts['title']), 0, 255),
      'summary' => trim((string) ($texts['summary'] ?? '')),
      // Trusted editors only (the page needs "Administer content"), but scripts are still removed.
      'body' => Xss::filterAdmin(trim((string) ($texts['body'] ?? ''))),
    ];
  }

  private function applyTexts(NodeInterface $node, array $texts, string $alias, int $created, bool $published): void {
    $node->setTitle($texts['title']);
    $node->set('field_content', [
      'value' => $texts['body'],
      'summary' => $texts['summary'],
      'format' => self::TEXT_FORMAT,
    ]);
    $node->setCreatedTime($created);
    $node->setPublished($published);
    $path = ['alias' => $alias];
    if ($this->moduleHandler->moduleExists('pathauto')) {
      // Keep the given URL instead of a generated one.
      $path['pathauto'] = 0;
    }
    $node->set('path', $path);
  }

  /** The Blogs node that already uses this URL, if any. */
  private function findByAlias(string $alias): ?NodeInterface {
    $aliases = $this->entityTypeManager->getStorage('path_alias')->loadByProperties(['alias' => $alias]);
    foreach ($aliases as $pathAlias) {
      if (preg_match('#^/node/(\d+)$#', $pathAlias->getPath(), $match)) {
        $node = $this->entityTypeManager->getStorage('node')->load($match[1]);
        if ($node instanceof NodeInterface) {
          if ($node->bundle() !== 'blogs') {
            throw new \RuntimeException("The URL $alias is already used by another page (node {$node->id()}).");
          }
          return $node;
        }
      }
    }
    return NULL;
  }

  /** "2026-11-10" -> that day at 09:00 in the site's time zone (now if missing). */
  private function timestamp(string $date): int {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      return \Drupal::time()->getRequestTime();
    }
    $time = new DrupalDateTime("$date 09:00:00");
    return $time->hasErrors() ? \Drupal::time()->getRequestTime() : $time->getTimestamp();
  }

  /** Saves one image of the archive as a Media (image) item and returns its id. */
  private function media(array $image): int {
    return $this->mediaSaver->save($this->archive, $image, self::IMAGE_DIRECTORY);
  }

}
