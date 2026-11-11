<?php

namespace Drupal\scanner\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displayed to confirm that the user want to undo the replace operation.
 */
class ScannerConfirmUndoForm extends ConfirmFormBase {

  use StringTranslationTrait;

  /**
   * Constructs a new ScannerConfirmUndoForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   */
  public function __construct(protected Connection $database) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'scanner_confirm_undo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    $form = parent::buildForm($form, $form_state);
    $form['undo_id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $connection = $this->database;
    $undo_id = $form_state->getValue('undo_id', 0);
    if (!empty($undo_id) && $undo_id > 0) {
      // Query the database in order to find the specific record we're trying
      // to undo.
      $query = $connection->query('SELECT undo_data FROM {scanner} WHERE undone = :undone AND undo_id = :id', [
        ':undone' => 0,
        ':id' => $undo_id,
      ]);
      $results = $query->fetchCol()[0];
      $data = unserialize($results, ['allowed_classes' => FALSE]);

      $operations[] = [
        '\Drupal\scanner\Form\ScannerConfirmUndoForm::batchUndo',
        [
          $data,
          $undo_id,
        ],
      ];

      $batch = [
        'title' => $this->t('Scanner Replace Batch'),
        'operations' => $operations,
        'finished' => '\Drupal\scanner\Form\ScannerConfirmUndoForm::batchFinished',
        'progress_message' => $this->t('Processed @current out of @total'),
      ];
      batch_set($batch);
    }
    $form_state->setRedirect('scanner.undo');
  }

  /**
   * Helper function to handle batch operation.
   *
   * @param array $data
   *   The array containing the revision ids for each entity.
   * @param string $undo_id
   *   The id of the undo record.
   * @param array $context
   *   The array containing the data which is persisted across jobs.
   */
  public static function batchUndo(array $data, string $undo_id, array &$context): void {
    // @todo Load this using DI.
    $pluginManager = \Drupal::service('plugin.manager.scanner');

    try {
      foreach ($data as $key => $value) {
        [$entityType] = explode(':', $key);
        $plugin = $pluginManager->createInstance("scanner_$entityType");
        // This process can take a while, so we want to extend the execution
        // time if it's less than 300 (5 minutes).
        if (ini_get('max_execution_time') < 300) {
          ini_set('max_execution_time', '300');
        }
        $plugin->undo($value);
        $context['results']['undo_id'] = $undo_id;
        $context['message'] = 'Undoing...';
      }
    }
    catch (PluginException $e) {
      // The instance could not be found so fail gracefully and let the user
      // know.
      \Drupal::logger('scanner')->error($e->getMessage());
      \Drupal::messenger()->addError(t('An error occurred @e:', ['@e' => $e->getMessage()]));
    }
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
    if ($success) {
      $connection = \Drupal::service('database');
      // Set the status of the record to '1', denoting being done.
      $connection->update('scanner')
        ->fields(['undone' => 1])
        ->condition('undo_id', $results['undo_id'], '=')
        ->execute();

      \Drupal::messenger()->addMessage(t('Undone successful for ID: @id.', [
        '@id' => $results['undo_id'],
      ]));
    }
    else {
      \Drupal::messenger()->addMessage(t('There were some errors.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('scanner.undo');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to undo this?');
  }

}
