<?php

namespace Drupal\scanner;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;

/**
 * The word boundaries helper.
 */
class WordBoundariesHelper {

  /**
   * The flood configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new WordBoundariesHelper object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(protected Connection $database, ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('scanner.admin_settings');
  }

  /**
   * Returns whether the PCRE syntax is supported.
   *
   * This is useful when doing case-sensitive regex matches on MySQL 8+.
   *
   * @return bool
   *   Returns if the database engine supports the inline PCRE flags.
   */
  public function supportsInlinePcreFlags(): bool {
    if ($this->database->databaseType() === 'mysql') {
      $db_version = $this->database->version();
      if (str_contains($db_version, '-')) {
        [$db_version] = explode('-', $db_version);
      }
      $is_mariadb = method_exists($this->database, 'isMariaDb') && $this->database->isMariaDb();
      if ($is_mariadb) {
        // MariaDB 10.0.5+ supports inline PCRE flags.
        // https://jira.mariadb.org/browse/MDEV-4425
        return version_compare($db_version, '10.0.5', '>=');
      }

      // MySQL 8.0.4+ supports PCRE flags through the "icu" library.
      // https://unicode-org.github.io/icu/userguide/strings/regexp.html#flag-options
      return version_compare($db_version, '8.0.4', '>=');
    }

    // Assume other implementations of the databases are modern enough to
    // support inline PCRE flags. The BINARY flag is MySQL specific anyway.
    return TRUE;
  }

  /**
   * Returns the correct inline regex prefix for case-sensitive matching.
   *
   * PostgreSQL uses Advanced Regular Expressions (ARE), where (?c) enables
   * case-sensitive matching. The other supported inline-regex engines use the
   * PCRE/ICU-compatible (?-i) form.
   *
   * @return string
   *   The database-specific inline case-sensitive regex prefix.
   */
  public function getCaseSensitiveRegexPrefix(): string {
    return $this->database->databaseType() === 'pgsql' ? '(?c)' : '(?-i)';
  }

  /**
   * Returns whether the REGEXP_LIKE function should be used.
   *
   * This is useful when doing case-sensitive regexp matches on MySQL 8.
   * It is not supported by MariaDB databases.
   *
   * NB: This is somewhat redundant if the engine supports PCRE flags.
   *
   * @see https://jira.mariadb.org/browse/MDEV-4425
   * @see https://dev.mysql.com/doc/refman/8.0/en/regexp.html#function_regexp-like
   *
   * @return bool
   *   Returns if the REGEXP_LIKE function should be used.
   */
  public function shouldUseRegexpLike(): bool {
    // REGEXP_LIKE is only supported on MySQL 8+. Ignore MariaDB instances.
    if (
      $this->database->databaseType() === 'mysql' &&
      !(method_exists($this->database, 'isMariaDb') && $this->database->isMariaDb())
    ) {
      $db_version = $this->database->version();
      if (str_contains($db_version, '-')) {
        [$db_version] = explode('-', $db_version);
      }
      if (version_compare($db_version, '8.0.22', '>=')) {
        // In MySQL 8.0.22 and later, use of a binary string with any of the
        // MySQL regular expression functions is rejected
        // with ER_CHARACTER_SET_MISMATCH.
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Work out whether to use the Spencer or ICU logic.
   *
   * @return string
   *   Either "spencer" or "icu".
   */
  public function shouldBe(): string {
    $should_be = 'spencer';

    if ($this->database->databaseType() === 'mysql') {
      $class = $this->database->getConnectionOptions()['namespace'] . '\\Install\\Tasks';
      $tasks = new $class();
      if (str_contains($tasks->name(), 'MySQL')) {
        // Only keep the first part. Hope this works.
        $db_version = $this->database->version();
        if (str_contains($db_version, '-')) {
          [$db_version] = explode('-', $db_version);
        }
        if (version_compare($db_version, '8.0.4', '>=')) {
          // Use the ICU implementation on MySQL 8.0.4+.
          $should_be = 'icu';
        }
      }
    }

    return $should_be;
  }

  /**
   * Determine which method to use, after checking the configuration.
   *
   * - "spencer" - Henry Spencer mode: [[:<:]] and [[:>:]]
   * Supported by all MariaDB versions and MySQL (8.0.3 and older).
   * - "icu" - ICU mode: \b.
   * Supported by MySQL (8.0.4 and newer) and MariaDB (10.0.5 and newer).
   *
   * @return string
   *   Either "spencer" or "icu".
   */
  public function whichToUse(): string {
    $word_boundaries = $this->config->get('word_boundaries');
    if (empty($word_boundaries) || $word_boundaries === 'auto') {
      return $this->shouldBe();
    }

    return $word_boundaries;
  }

}
