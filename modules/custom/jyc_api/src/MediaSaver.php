<?php

declare(strict_types=1);

namespace Drupal\jyc_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\media\MediaInterface;

/**
 * Saves images of an import archive as Media (image) items.
 *
 * An image with the same file name and content as one already saved in the
 * target folder is reused (with its media item), so importing twice doesn't
 * create copies. One instance per import run.
 */
final class MediaSaver {

  private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

  private const MAX_IMAGE_BYTES = 20 * 1024 * 1024;

  /** New or changed image files written in this run. */
  public int $saved = 0;

  /** Media ids of images already handled in this run, by archive path. */
  private array $mediaIds = [];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileSystemInterface $fileSystem,
    private readonly FileRepositoryInterface $fileRepository,
    private readonly AccountInterface $currentUser,
  ) {}

  /**
   * Saves { "file": "images/x.jpg", "alt": "…" } from the archive into
   * $directory (e.g. public://blogs) and returns the media item's id.
   *
   * @throws \RuntimeException
   */
  public function save(ImportArchive $archive, array $image, string $directory): int {
    $file = str_replace('\\', '/', trim((string) ($image['file'] ?? '')));
    $alt = mb_substr(trim((string) ($image['alt'] ?? '')), 0, 512);
    if (isset($this->mediaIds[$file])) {
      return $this->mediaIds[$file];
    }

    $name = basename($file);
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($file === '' || str_contains($file, '..') || str_starts_with($file, '/') || !in_array($extension, self::IMAGE_EXTENSIONS, TRUE)
      || !preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
      throw new \RuntimeException("Invalid image \"$file\" (use jpg, jpeg, png, webp or gif, with a plain file name).");
    }

    $data = $archive->read($file);
    if ($data === NULL || $data === '') {
      throw new \RuntimeException("Image \"$file\" is not in the archive.");
    }
    if (strlen($data) > self::MAX_IMAGE_BYTES || @getimagesizefromstring($data) === FALSE) {
      throw new \RuntimeException("\"$file\" is not a valid image or is larger than 20 MB.");
    }

    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $uri = rtrim($directory, '/') . "/$name";

    // Same name and same content: reuse the file and its media item as they are.
    $fileEntity = $this->fileRepository->loadByUri($uri);
    if ($fileEntity === NULL || @md5_file($this->fileSystem->realpath($uri) ?: $uri) !== md5($data)) {
      $fileEntity = $this->fileRepository->writeData($data, $uri, FileExists::Replace);
      $this->saved++;
    }
    if (!$fileEntity->isPermanent()) {
      $fileEntity->setPermanent();
      $fileEntity->save();
    }

    $mediaStorage = $this->entityTypeManager->getStorage('media');
    $ids = $mediaStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'image')
      ->condition('field_media_image.target_id', $fileEntity->id())
      ->range(0, 1)
      ->execute();
    $media = $ids ? $mediaStorage->load(reset($ids)) : NULL;
    if (!$media instanceof MediaInterface) {
      $media = $mediaStorage->create([
        'bundle' => 'image',
        'uid' => $this->currentUser->id(),
        'status' => 1,
      ]);
    }
    $media->setName($alt !== '' ? mb_substr($alt, 0, 255) : $name);
    $media->set('field_media_image', ['target_id' => $fileEntity->id(), 'alt' => $alt !== '' ? $alt : $name]);
    $media->save();

    return $this->mediaIds[$file] = (int) $media->id();
  }

}
