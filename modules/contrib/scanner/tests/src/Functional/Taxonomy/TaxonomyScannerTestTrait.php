<?php

namespace Drupal\Tests\scanner\Functional\Taxonomy;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;

/**
 * Taxonomy-specific helpers for scanner functional tests.
 */
trait TaxonomyScannerTestTrait {

  /**
   * Create a vocabulary.
   *
   * @param array $values
   *   Items passed to the vocabulary. If the 'vid' item is not present it will
   *   be automatically generated. If the 'name' item is not present the 'vid'
   *   will be used.
   *
   * @return \Drupal\taxonomy\VocabularyInterface|\Drupal\Core\Entity\EntityInterface
   *   A fully formatted vocabulary object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createVocabulary(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['vid'])) {
      do {
        $id = strtolower($this->randomMachineName());
      } while (Vocabulary::load($id));
    }
    else {
      $id = $values['vid'];
    }
    $values += [
      'vid' => $id,
      'name' => $id,
    ];
    $vocab = Vocabulary::create($values);
    $status = $vocab->save();

    // Add the standard test fields.
    $this->addTestFields('taxonomy_term', $id);

    $this->assertEquals(SAVED_NEW, $status, (new FormattableMarkup('Created vocabulary %type.', ['%type' => $vocab->id()]))->__toString());

    return $vocab;
  }

  /**
   * Create a taxonomy term.
   *
   * @param array $values
   *   Items passed to the term. Requires the 'vid' element.
   *
   * @return \Drupal\taxonomy\TermInterface|\Drupal\Core\Entity\EntityInterface
   *   A fully formatted term object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTerm(array $values = []): TermInterface|EntityInterface {
    // Populate defaults array.
    $values += [
      'description' => [
        [
          'value' => $this->randomMachineName(32),
          // @phpstan-ignore function.deprecated
          'format' => DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(), fn() => filter_default_format()),
        ],
      ],
      'name' => $this->randomMachineName(8),
    ];
    $term = Term::create($values);
    $status = $term->save();

    $this->assertEquals(SAVED_NEW, $status, (new FormattableMarkup('Created term %name.', ['%name' => $term->label()]))->__toString());

    return $term;
  }

}
