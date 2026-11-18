<?php

declare(strict_types=1);

namespace Drupal\shortcode;

use Drupal\Core\Language\LanguageInterface;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\shortcode\Plugin\ShortcodeInterface;

/**
 * Provide the ShortCode service.
 */
class ShortcodeService {

  /**
   * Static cache of shortcode plugin tokens, keyed by token.
   *
   * @var array|null
   */
  protected ?array $shortcodePluginTokens = NULL;

  /**
   * Static cache of shortcode plugin definitions, keyed by filter/context.
   *
   * @var array|null
   */
  protected ?array $shortcodePlugins = NULL;

  /**
   * Static cache of instantiated shortcode plugins, keyed by plugin ID.
   *
   * @var \Drupal\shortcode\Plugin\ShortcodeInterface[]
   */
  protected array $shortcodePluginInstances = [];

  /**
   * Constructs a ShortcodeService object.
   *
   * @param \Drupal\shortcode\ShortcodePluginManager $shortCodePluginManager
   *   The config factory service.
   */
  public function __construct(protected ShortcodePluginManager $shortCodePluginManager) {
  }

  /**
   * Returns preprocessed ShortCode plugin definitions.
   *
   * @return array
   *   Array of ShortCode plugin definitions.
   */
  public function loadShortcodePlugins(): array {

    $definitions_raw = $this->shortCodePluginManager->getDefinitions();

    $definitions = [];
    foreach ($definitions_raw as $shortcode_id => $definition) {

      // Default weight to 99.
      if (!isset($definition['weight'])) {
        $definition['weight'] = 99;
      }

      // Default token to id.
      $token = strtolower($definition['token'] ?? $shortcode_id);
      $definition['token'] = $token;

      $definitions[$shortcode_id] = $definition;
    }

    return $definitions;
  }

  /**
   * Returns array of shortcode plugin definitions enabled for the filter.
   *
   * @param bool $reset
   *   TRUE if the static cache should be reset. Defaults to FALSE.
   *
   * @return array
   *   Array of shortcode plugin definitions.
   */
  public function getShortcodePluginTokens(bool $reset = FALSE): array {
    // Prime plugin cache.
    if ($this->shortcodePluginTokens === NULL || $reset) {
      $shortcodes = $this->loadShortcodePlugins();
      $tokens = [];
      foreach ($shortcodes as $shortcode) {
        $tokens[$shortcode['token']] = $shortcode['token'];
      }
      $this->shortcodePluginTokens = $tokens;
    }

    return $this->shortcodePluginTokens;
  }

  /**
   * Returns array of shortcode plugin definitions enabled for the filter.
   *
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The filter. Defaults to NULL, where all shortcode plugins will be
   *   returned.
   * @param bool $reset
   *   TRUE if the static cache should be reset. Defaults to FALSE.
   *
   * @return array
   *   Array of shortcode plugin definitions, keyed by token, not id.
   */
  public function getShortcodePlugins(?FilterInterface $filter = NULL, bool $reset = FALSE): array {
    // Prime plugin cache.
    if ($this->shortcodePlugins === NULL || $reset) {

      $shortcodes_by_id = $this->loadShortcodePlugins();

      // Sorting plugins by weight using uasort() costs quite a bit more than
      // what we're doing below.
      $definitions = [];
      foreach ($shortcodes_by_id as $shortcode) {
        $token = $shortcode['token'];
        // Only replace the definition if weight is smaller.
        if (!isset($definitions[$token]) || ($definitions[$token]['weight'] < $shortcode['weight'])) {
          $definitions[$token] = $shortcode;
        }
      }

      $this->shortcodePlugins = [
        'all' => $shortcodes_by_id,
        'default' => $definitions,
      ];
    }

    // If filter is given, only return plugin definitions enabled on the filter.
    if ($filter) {
      $filter_id = $filter->getPluginId();
      if (!isset($this->shortcodePlugins[$filter_id])) {
        $settings = $filter->getConfiguration()['settings'] ?? [];

        $shortcodes_by_id = $this->shortcodePlugins['all'];
        $enabled_shortcodes = [];
        foreach ($shortcodes_by_id as $shortcode_id => $shortcode) {
          if (isset($settings[$shortcode_id]) && $settings[$shortcode_id]) {
            $token = $shortcode['token'];
            // Only replace the definition if weight is smaller.
            if (!isset($enabled_shortcodes[$token]) || ($enabled_shortcodes[$token]['weight'] < $shortcode['weight'])) {
              $enabled_shortcodes[$token] = $shortcode;
            }
          }
        }

        $this->shortcodePlugins[$filter_id] = $enabled_shortcodes;
      }

      return $this->shortcodePlugins[$filter_id];
    }

    // Return all defined shortcode plugin definitions keyed by token.
    return $this->shortcodePlugins['default'];
  }

  /**
   * Creates ShortCode plugin instance or loads from static cache.
   *
   * @param string $shortcode_id
   *   The ShortCode plugin id.
   *
   * @return \Drupal\shortcode\Plugin\ShortcodeInterface
   *   The plugin instance.
   */
  public function getShortcodePlugin(string $shortcode_id): ShortcodeInterface {
    if (!isset($this->shortcodePluginInstances[$shortcode_id])) {
      $this->shortcodePluginInstances[$shortcode_id] = $this->shortCodePluginManager->createInstance($shortcode_id);
    }
    return $this->shortcodePluginInstances[$shortcode_id];
  }

  /**
   * Checking the given tag is valid ShortCode tag or not.
   *
   * @param string $tag
   *   The tag name.
   *
   * @return bool
   *   Returns TRUE if the given $tag is valid ShortCode tag.
   */
  public function isValidShortcodeTag($tag): bool {
    $tokens = $this->getShortcodePluginTokens();
    // @todo This is case-sensitive right now, consider if it should be.
    return isset($tokens[$tag]);
  }

  /**
   * Processes the Shortcodes according to the text and the text format.
   *
   * @param string $text
   *   The string containing shortcodes to be processed.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The text filter.
   *
   * @return string
   *   The processed string.
   */
  public function process($text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, ?FilterInterface $filter = NULL): string {
    $shortcodes = $this->getShortcodePlugins($filter);
    $heap = [];
    $heap_index = [];

    foreach ($this->splitIntoChunks($text) as $chunk) {
      if (!$chunk) {
        continue;
      }
      $this->processChunk($chunk, $shortcodes, $heap, $heap_index);
    }

    return implode('', array_reverse($heap));
  }

  /**
   * Splits text into shortcode-tag and plain-text chunks.
   */
  protected function splitIntoChunks(string $text): array {
    return preg_split('!(\[{1,2}.*?\]{1,2})!', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
  }

  /**
   * Strips escaping brackets from a chunk and reports whether it was escaped.
   *
   * @return array{0: string, 1: bool}
   *   The normalized chunk text and whether it was escaped.
   */
  protected function normalizeEscapedChunk(string $chunk): array {
    $c = $chunk;
    $escaped = FALSE;
    if ((substr($c, 0, 2) == '[[') && (substr($c, -2, 2) == ']]')) {
      $escaped = TRUE;
      // Checks media tags, eg: [[{ }]].
      if ((substr($c, 0, 3) != '{') && (substr($c, -3, 1) != '}')) {
        $c = substr($c, 1, -1);
      }
    }
    return [$c, $escaped];
  }

  /**
   * Determines whether a normalized chunk looks like a shortcode tag.
   */
  protected function looksLikeShortcodeTag(string $c, bool $escaped): bool {
    return !$escaped && ($c[0] == '[') && (substr($c, -1, 1) == ']');
  }

  /**
   * Pushes a plain-text chunk onto the processing heap.
   */
  protected function pushStringChunk(string $text, array &$heap, array &$heap_index): void {
    array_unshift($heap_index, '_string_');
    array_unshift($heap, $text);
  }

  /**
   * Processes and pushes the result of a self-closing tag.
   */
  protected function pushSelfClosingTagResult(string $c, string $tag, array $ts, array $shortcodes, array &$heap, array &$heap_index): void {
    $m = [$c, '', $tag, implode(' ', $ts), '', ''];
    $this->pushStringChunk($this->processTag($m, $shortcodes), $heap, $heap_index);
  }

  /**
   * Handles a closing tag by popping and processing the matching heap span.
   */
  protected function processClosingTag(string $closing_tag, array $shortcodes, array &$heap, array &$heap_index): void {
    $process_heap = [];
    $process_heap_index = [];
    $found = FALSE;

    do {
      $tag = array_shift($heap_index);
      $heap_text = array_shift($heap);

      if ($closing_tag == $tag) {
        $m = [$tag . ' ' . $heap_text, '', $tag, $heap_text, implode('', $process_heap), ''];
        $this->pushStringChunk($this->processTag($m, $shortcodes), $heap, $heap_index);
        $found = TRUE;
      }
      else {
        array_unshift($process_heap, $heap_text);
        array_unshift($process_heap_index, $tag);
      }
    } while (!$found && $heap);

    if (!$found) {
      foreach ($process_heap as $val) {
        array_unshift($heap, $val);
      }
      foreach ($process_heap_index as $val) {
        array_unshift($heap_index, $val);
      }
    }
  }

  /**
   * Pushes an opening tag onto the heap awaiting its closing tag.
   */
  protected function pushOpeningTag(string $tag, array $ts, array &$heap, array &$heap_index): void {
    array_unshift($heap_index, $tag);
    array_unshift($heap, implode(' ', $ts));
  }

  /**
   * Classifies and dispatches a single chunk during process().
   */
  protected function processChunk(string $chunk, array $shortcodes, array &$heap, array &$heap_index): void {
    [$c, $escaped] = $this->normalizeEscapedChunk($chunk);

    if (!$this->looksLikeShortcodeTag($c, $escaped)) {
      $this->pushStringChunk($c, $heap, $heap_index);
      return;
    }

    $original_text = $c;
    $c = trim(substr($c, 1, -1));
    $ts = explode(' ', $c);
    $tag = trim(array_shift($ts), '/');

    if (!$this->isValidShortcodeTag($tag)) {
      $this->pushStringChunk($original_text, $heap, $heap_index);
      return;
    }

    if (substr($c, -1, 1) == '/') {
      $this->pushSelfClosingTagResult($c, $tag, $ts, $shortcodes, $heap, $heap_index);
      return;
    }

    if ($c[0] == '/') {
      $this->processClosingTag(substr($c, 1), $shortcodes, $heap, $heap_index);
      return;
    }

    $this->pushOpeningTag($tag, $ts, $heap, $heap_index);
  }

  /**
   * Provides Html corrector for wysiwyg editors.
   *
   * Correcting p elements around the divs. <div> elements are not allowed
   * in <p> so remove them.
   *
   * @param string $text
   *   Text to be processed.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The filter plugin that triggered this process.
   *
   * @return string
   *   The processed string.
   */
  public function postprocessText($text, string $langcode, ?FilterInterface $filter = NULL): string {

    // Take note these are disrupted by the comments inserted by twig debug
    // mode.
    $patterns = [
      '|#!#|is',
      '!<p>(&nbsp;|\s)*(<\/*div>)!is',
      '!<p>(&nbsp;|\s)*(<div)!is',
      '!(<\/div.*?>)\s*</p>!is',
      '!(<div.*?>)\s*</p>!is',
    ];

    $replacements = [
      '',
      '\\2',
      '\\2',
      '\\1',
      '\\1',
    ];
    return preg_replace($patterns, $replacements, $text);
  }

  /**
   * Regular Expression callable for do_shortcode() for calling Shortcode hook.
   *
   * See for details of the match array contents.
   *
   * @param array $m
   *   Regular expression match array.
   *
   *     0 - the full tag text?
   *     1/5 - An extra [ or ] to allow for escaping shortcodes with double [[]]
   *     2 - The Shortcode name
   *     3 - The Shortcode argument list
   *     4 - The content of a Shortcode when it wraps some content.
   * @param array $enabled_shortcodes
   *   Array of enabled shortcodes for the active text format.
   *
   * @return string
   *   Processed tag.
   */
  protected function processTag(array $m, array $enabled_shortcodes): string {
    $shortcode_token = $m[2];

    $shortcode = NULL;
    if (isset($enabled_shortcodes[$shortcode_token])) {
      $shortcode_id = $enabled_shortcodes[$shortcode_token]['id'];
      $shortcode = $this->getShortcodePlugin($shortcode_id);
    }

    // If shortcode does not exist or is not enabled, return input sans tokens.
    if (empty($shortcode)) {
      // This is an enclosing tag, means extra parameter is present.
      if (!is_null($m[4])) {
        return $m[1] . $m[4] . $m[5];
      }
      return $m[1] . $m[5];
    }

    // Process if shortcode exists and enabled.
    $attr = $this->parseAttrs($m[3]);
    return $m[1] . $shortcode->process($attr, $m[4]) . $m[5];
  }

  /**
   * Retrieve all attributes from the Shortcodes tag.
   *
   * The attributes list has the attribute name as the key and the value of the
   * attribute as the value in the key/value pair. This allows for easier
   * retrieval of the attributes, since all attributes have to be known.
   *
   * @param string $text
   *   The Shortcode tag attribute line.
   *
   * @return array
   *   List of attributes and their value.
   */
  protected function parseAttrs(string $text): array {
    $attributes = [];
    if (empty($text)) {
      return $attributes;
    }
    $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
    $text = html_entity_decode($text);
    if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
      foreach ($match as $m) {
        if (!empty($m[1])) {
          $attributes[strtolower($m[1])] = stripcslashes($m[2]);
        }
        elseif (!empty($m[3])) {
          $attributes[strtolower($m[3])] = stripcslashes($m[4]);
        }
        elseif (!empty($m[5])) {
          $attributes[strtolower($m[5])] = stripcslashes($m[6]);
        }
        elseif (isset($m[7]) and strlen($m[7])) {
          $attributes[] = stripcslashes($m[7]);
        }
        elseif (isset($m[8])) {
          $attributes[] = stripcslashes($m[8]);
        }
      }
    }
    else {
      $attributes = ltrim($text);
    }
    return $attributes;
  }

}
