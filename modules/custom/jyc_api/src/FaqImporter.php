<?php

declare(strict_types=1);

namespace Drupal\jyc_api;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports "FAQ" nodes (English + Arabic) from an archive with a faqs.json:
 *
 *   {
 *     "format": "jyc-faqs", "version": 1,
 *     "faqs": [{
 *       "category": 1,     -> field_faq_taxonomy (faq_taxonomy term id)
 *       "weight": 10,      -> field_weigth (lower comes first)
 *       "en": { "question": "…", "answer": "<p>…</p>" },   -> title, field_content
 *       "ar": { "question": "…", "answer": "<p>…</p>" }    (optional translation)
 *     }]
 *   }
 *
 * An FAQ with the same category and the same question (in the default
 * language) is updated instead of created again, so importing the same
 * archive twice is safe.
 */
final class FaqImporter {

  private const TEXT_FORMAT = 'full_html';

  private const VOCABULARY = 'faq_taxonomy';

  private const MAX_FAQS = 1000;

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
   * Imports the FAQs of an opened archive (faqs.json already read).
   *
   * @return array{created: int, updated: int, errors: string[]}
   *
   * @throws \RuntimeException
   */
  public function importFrom(ImportArchive $archive, array $manifest): array {
    if (($manifest['format'] ?? '') !== 'jyc-faqs' || !is_array($manifest['faqs'] ?? NULL)) {
      throw new \RuntimeException('faqs.json is not a JYC FAQ file (expected "format": "jyc-faqs" and a "faqs" list).');
    }
    $this->result = ['created' => 0, 'updated' => 0, 'errors' => []];

    foreach (array_slice($manifest['faqs'], 0, self::MAX_FAQS) as $index => $faq) {
      try {
        $this->importFaq(is_array($faq) ? $faq : []);
      }
      catch (\Throwable $e) {
        $question = is_array($faq) ? (string) ($faq['en']['question'] ?? '') : '';
        $this->result['errors'][] = '#' . ($index + 1) . ($question !== '' ? " ($question)" : '') . ': ' . $e->getMessage();
      }
    }
    return $this->result;
  }

  private function importFaq(array $faq): void {
    $default = $this->languageManager->getDefaultLanguage()->getId();
    $texts = $this->texts($faq[$default] ?? $faq['en'] ?? NULL);
    if ($texts === NULL) {
      throw new \RuntimeException("A question in the default language ($default) is required.");
    }

    $termId = (int) ($faq['category'] ?? 0);
    $term = $termId > 0 ? $this->entityTypeManager->getStorage('taxonomy_term')->load($termId) : NULL;
    if (!$term instanceof TermInterface || $term->bundle() !== self::VOCABULARY) {
      throw new \RuntimeException("Category $termId is not a term of the FAQ vocabulary.");
    }
    $weight = (int) ($faq['weight'] ?? 0);
    $published = ($faq['published'] ?? TRUE) !== FALSE;

    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'faq')
      ->condition('field_faq_taxonomy', $termId)
      ->condition('title', $texts['question'])
      ->condition('langcode', $default)
      ->range(0, 1)
      ->execute();
    $node = $ids ? $storage->load(reset($ids)) : NULL;
    $isNew = !$node instanceof NodeInterface;
    if ($isNew) {
      $node = $storage->create(['type' => 'faq', 'langcode' => $default, 'uid' => $this->currentUser->id()]);
    }
    elseif ($node->hasTranslation($default)) {
      $node = $node->getTranslation($default);
    }

    $this->applyTexts($node, $texts, $published);
    $node->set('field_faq_taxonomy', ['target_id' => $termId]);
    $node->set('field_weigth', $weight);

    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if ($langcode === $default || ($other = $this->texts($faq[$langcode] ?? NULL)) === NULL) {
        continue;
      }
      $translation = $node->hasTranslation($langcode) ? $node->getTranslation($langcode) : $node->addTranslation($langcode);
      $this->applyTexts($translation, $other, $published);
      if ($translation->hasField('content_translation_source') && $translation->get('content_translation_source')->isEmpty()) {
        $translation->set('content_translation_source', $default);
      }
    }

    if ($node->getEntityType()->isRevisionable()) {
      $node->setNewRevision();
      $node->setRevisionUserId($this->currentUser->id());
      $node->setRevisionLogMessage('Imported from an FAQ archive.');
    }
    $node->save();
    $this->result[$isNew ? 'created' : 'updated']++;
  }

  /** Question and answer of one language, or NULL when there is no question. */
  private function texts(mixed $texts): ?array {
    if (!is_array($texts) || trim((string) ($texts['question'] ?? '')) === '') {
      return NULL;
    }
    return [
      'question' => mb_substr(trim((string) $texts['question']), 0, 255),
      // Trusted editors only, but scripts are still removed.
      'answer' => Xss::filterAdmin(trim((string) ($texts['answer'] ?? ''))),
    ];
  }

  private function applyTexts(NodeInterface $node, array $texts, bool $published): void {
    $node->setTitle($texts['question']);
    $node->set('field_content', ['value' => $texts['answer'], 'format' => self::TEXT_FORMAT]);
    $node->setPublished($published);
  }

}
