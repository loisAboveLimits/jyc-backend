<?php

declare(strict_types=1);

namespace Drupal\jyc_api;

use Drupal\Core\Archiver\ArchiveTar;

/**
 * Read-only access to a .tar.gz / .tgz / .tar.bz2 import archive.
 *
 * Files are read straight from the archive (nothing is unpacked to disk).
 * The manifest (e.g. posts.json) may sit at the top of the archive or inside
 * one folder; other paths are relative to the manifest's folder.
 */
final class ImportArchive {

  /** Folder of the manifest inside the archive ('' = top level). */
  private string $base = '';

  private function __construct(private readonly ArchiveTar $tar) {}

  /**
   * Opens an archive, working out gzip / bzip2 from its first bytes.
   *
   * @throws \RuntimeException
   */
  public static function open(string $path): self {
    $head = (string) @file_get_contents($path, FALSE, NULL, 0, 3);
    if (str_starts_with($head, "\x1f\x8b")) {
      $type = 'gz';
      if (!function_exists('gzopen')) {
        throw new \RuntimeException('PHP cannot read .tar.gz files on this server (the zlib extension is missing).');
      }
    }
    elseif (str_starts_with($head, 'BZh')) {
      $type = 'bz2';
      if (!function_exists('bzopen')) {
        throw new \RuntimeException('PHP cannot read .tar.bz2 files on this server: enable the bz2 extension (extension=bz2 in php.ini, then restart Apache), or import the .tar.gz / .tgz version instead.');
      }
    }
    else {
      throw new \RuntimeException('This is not a .tar.gz, .tgz or .tar.bz2 archive.');
    }

    try {
      return new self(new ArchiveTar($path, $type));
    }
    catch (\Exception $e) {
      throw new \RuntimeException('The archive could not be opened: ' . $e->getMessage());
    }
  }

  /**
   * Finds the first of the given manifest names (at the top or one folder
   * deep), remembers its folder and returns [name, decoded JSON], or NULL
   * when none of them is in the archive.
   *
   * @throws \RuntimeException
   */
  public function manifest(string ...$names): ?array {
    try {
      $entries = $this->tar->listContent();
    }
    catch (\Exception $e) {
      throw new \RuntimeException('The archive could not be read: ' . $e->getMessage());
    }

    $found = NULL;
    foreach ((array) $entries as $entry) {
      $raw = (string) ($entry['filename'] ?? '');
      $name = ltrim(str_replace('\\', '/', $raw), './');
      $depth = substr_count($name, '/');
      if (in_array(basename($name), $names, TRUE) && $depth <= 1 && ($found === NULL || $depth < $found['depth'])) {
        $found = ['raw' => $raw, 'name' => $name, 'depth' => $depth];
      }
    }
    if ($found === NULL) {
      return NULL;
    }

    $folder = dirname($found['name']);
    $this->base = $folder === '.' ? '' : $folder . '/';
    $data = json_decode((string) $this->tar->extractInString($found['raw']), TRUE);
    if (!is_array($data)) {
      throw new \RuntimeException(basename($found['name']) . ' is not valid JSON.');
    }
    return [basename($found['name']), $data];
  }

  /** A file of the archive, relative to the manifest's folder, or NULL if missing. */
  public function read(string $path): ?string {
    $data = $this->tar->extractInString($this->base . $path);
    if (!is_string($data) && $this->base !== '') {
      $data = $this->tar->extractInString($path);
    }
    return is_string($data) ? $data : NULL;
  }

}
