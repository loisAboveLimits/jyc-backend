<?php

declare(strict_types=1);

namespace Drupal\shortcode;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\media\MediaInterface;

/**
 * Resolves media file URLs and image properties for shortcode plugins.
 */
class MediaUrlResolver implements MediaUrlResolverInterface {

  /**
   * Constructs a MediaUrlResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFileUrl(int|string $mid): string|false {
    /** @var \Drupal\media\MediaInterface|null $media_entity */
    $media_entity = $this->entityTypeManager->getStorage('media')->load($mid);
    // The original ShortcodeBase::getMediaFileUrl() called getMediaField()
    // unconditionally here, which threw a TypeError for a nonexistent media
    // ID (reachable via a Button/Link shortcode pointing at deleted media).
    // This null-guard is a deliberate, isolated bugfix, not new behavior.
    $field_media = $media_entity ? $this->getMediaField($media_entity) : FALSE;
    if ($field_media) {
      $file = $field_media->entity;
      return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageProperties(int|string $mid): array {
    $properties = ['alt' => '', 'path' => ''];
    $media_entity = NULL;
    $field_media = NULL;
    $file = NULL;

    if ((int) $mid) {
      /** @var \Drupal\media\MediaInterface|null $media_entity */
      $media_entity = $this->entityTypeManager->getStorage('media')->load($mid);
    }
    if ($media_entity) {
      $field_media = $this->getMediaField($media_entity);
    }
    if ($field_media) {
      $file = $field_media->entity;
      if (isset($field_media->alt)) {
        $properties['alt'] = $field_media->alt;
      }
    }
    if ($file) {
      $properties['path'] = $file->getFileUri();
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyleUrl(string $imageStyleId, string $uri): string|false {
    $image_style = $this->entityTypeManager->getStorage('image_style')->load($imageStyleId);
    if ($image_style) {
      return $image_style->buildUrl($uri);
    }
    return FALSE;
  }

  /**
   * Gets a media entity's file/image/video/audio field, if present.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   The media entity.
   *
   * @return mixed
   *   The field item list, or FALSE if none of the known field names exist.
   */
  private function getMediaField(MediaInterface $entity): mixed {
    $media_file_fields = [
      'field_media_file',
      'field_media_image',
      'field_media_video_file',
      'field_media_audio_file',
    ];
    foreach ($media_file_fields as $field_name) {
      if ($entity->hasField($field_name)) {
        return $entity->get($field_name);
      }
    }
    return FALSE;
  }

}
