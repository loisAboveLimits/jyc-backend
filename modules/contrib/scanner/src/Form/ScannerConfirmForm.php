<?php

namespace Drupal\scanner\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configure messages.
 */
class ScannerConfirmForm extends ConfirmFormBase {

  use StringTranslationTrait;

  /**
   * Constructs a ScannerConfirmForm object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStore
   *   The private tempstore factory service.
   */
  public function __construct(protected PrivateTempStoreFactory $tempStore) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'scanner_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $store = $this->tempStore->get('scanner');
    $msg = $this->t('<span>Search for: </span><strong>@search</strong><br><span>Replace with: </span><strong>@replace</strong>', [
      '@search' => $store->get('search'),
      '@replace' => $store->get('replace'),
    ]);
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $msg,
    ];

    // Display the search results to show what will be changed.
    $form['results'] = $store->get('results_output');
    $form['results']['#title'] = $this->t('Content that will be changed');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $store = $this->tempStore->get('scanner');
    foreach ([
      'search',
      'replace',
      'mode',
      'wholeword',
      'regex',
      'preceded',
      'followed',
      'published',
      'language',
    ] as $value) {
      $values[$value] = $store->get($value);
    }

    $operations = [];
    // Build an array of batch operation jobs. Batch job will need the field
    // and the filter values the users entered in the form.
    foreach ($store->get('results') as $entity_type => $results) {
      $operations[] = [
        '\Drupal\scanner\Form\ScannerConfirmForm::batchReplace',
        [
          $entity_type,
          $results,
          $values,
        ],
      ];
    }
    $batch = [
      'title' => $this->t('Scanner Replace Batch'),
      'operations' => $operations,
      'finished' => '\Drupal\scanner\Form\ScannerConfirmForm::batchFinished',
      'progress_message' => $this->t('Processed @current out of @total'),
    ];
    batch_set($batch);
    // Redirect to the scanner page after the batch is done.
    $form_state->setRedirect('scanner.admin_content');
  }

  /**
   * Process the replacement.
   */
  public static function batchReplace(string $entity_type, array $results, array $values, &$context): void {
    $pluginManager = \Drupal::service('plugin.manager.scanner');

    try {
      $plugin = $pluginManager->createInstance('scanner_' . $entity_type);
      // This process can take a while so we want to extend the execution time
      // if it's less then 300 (5 minutes).
      if (ini_get('max_execution_time') < 300) {
        ini_set('max_execution_time', 300);
      }
    }
    catch (PluginException $e) {
      // The instance could not be found so fail gracefully and let the user
      // know.
      \Drupal::logger('scanner')->error($e->getMessage());
      \Drupal::messenger()->addError(t('An error occurred @e:', ['@e' => $e->getMessage()]));
    }

    if (!isset($context['results']['data'])) {
      $context['results']['data'] = [];
    }

    $data = $plugin->replace($results, $values, $context['results']['data']);

    // Save the undo data for later.
    foreach (array_keys($data) as $entity_key) {
      $context['results']['data'][$entity_key] = $data[$entity_key];
    }

    $context['results']['inputs'] = [
      'search' => $values['search'],
      'replace' => $values['replace'],
    ];
    $context['message'] = t('Replacing text on @type.', ['@type' => (string) $results['label']]);
  }

  /**
   * The batch process has finished.
   *
   * @param bool $success
   *   Indicates whether the batch process finish successfully.
   * @param array $results
   *   Contains the output from the batch operations.
   * @param array $operations
   *   A list of operations that were processed.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['data'])) {
        $results['count'] = count($results['data']);
        $messenger->addMessage(t('@count entities processed.', [
          '@count' => $results['count'],
        ]));
        $connection = \Drupal::service('database');
        // Insert to row into the scanner table so that the
        // action can be undone in the future.
        $connection->insert('scanner')
          ->fields([
            'undo_data' => serialize($results['data']),
            'undone' => 0,
            'searched' => $results['inputs']['search'],
            'replaced' => $results['inputs']['replace'],
            'count' => $results['count'],
            'time' => \Drupal::time()->getRequestTime(),
          ])
          ->execute();
      }
    }
    else {
      $message = t('There were some errors.');
      $messenger->addMessage($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    $this->tempStore->get('scanner')->set('scanner_op', '');
    return new Url('scanner.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to make the following replacement?');
  }

}
