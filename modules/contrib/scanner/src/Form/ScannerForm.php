<?php

namespace Drupal\scanner\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\scanner\AdminHelper;
use Drupal\scanner\Plugin\ScannerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for performing searching.
 */
class ScannerForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Constructs a ScannerForm object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStore
   *   The private temp story factory.
   * @param \Drupal\scanner\Plugin\ScannerPluginManager $scannerManager
   *   The scanner manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Render\Renderer $render
   *   The renderer.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected PrivateTempStoreFactory $tempStore,
    protected ScannerPluginManager $scannerManager,
    protected LanguageManagerInterface $languageManager,
    protected Renderer $render,
    protected AccountProxyInterface $currentUser,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.scanner'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'scanner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory()->get('scanner.admin_settings');
    $fields = $config->get('fields_of_selected_content_type');
    $config_page = Url::fromRoute('scanner.admin_config')->toString();

    if (empty($fields)) {
      $form['settings_link'] = [
        '#prefix' => '<p>',
        '#markup' => $this->t('The search fields were not configured. To configure them go to <a href="@href">Search and Replace Scanner configuration</a>', [
          '@href' => $config_page,
        ]),
        '#suffix' => '</p>',
      ];

      return $form;
    }

    $mode = $form_state->getValue('scanner_mode') ? $form_state->getValue('scanner_mode') : $config->get('scanner_mode');
    $wholeword = $form_state->getValue('scanner_wholeword') ? $form_state->getValue('scanner_wholeword') : $config->get('scanner_wholeword');
    $regex = $form_state->getValue('scanner_regex') ? $form_state->getValue('scanner_regex') : $config->get('scanner_regex');
    $published = $form_state->getValue('scanner_published') ? $form_state->getValue('scanner_published') : $config->get('scanner_published');
    $language = $form_state->getValue('scanner_language') ? $form_state->getValue('scanner_language') : $config->get('scanner_language');

    $form['settings_link'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('To configure settings go to <a href="@href">Search and Replace Scanner configuration</a>', [
        '@href' => $config_page,
      ]),
      '#suffix' => '</p>',
    ];
    $form['search'] = [
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => $this->t('Step 1: Search for'),
      '#description' => $this->t('Enter the search term to search for.'),
      '#maxlength' => 256,
    ];
    $form['submit_search'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#suffix' => '<hr>',
    ];

    $form['replace'] = [
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => $this->t('Step 2: Replace with'),
      '#description' => $this->t('Enter the term to replace the searched term.'),
      '#maxlength' => 256,
      '#access' => $this->currentUser->hasPermission('perform search and replace'),
    ];
    $form['submit_replace'] = [
      '#type' => 'submit',
      '#value' => $this->t('Replace'),
      '#access' => $this->currentUser->hasPermission('perform search and replace'),
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Search Options'),
    ];

    $form['options']['surrounding'] = [
      '#type' => 'details',
      '#title' => $this->t('Surrounding Text'),
      '#description' => $this->t('You can limit matches by providing the text that should appear immediately before or after the search text. Remember to account for spaces.  Note: Case sensitivity and regular expression options will all apply here, too. Whole word is not recommended.'),
    ];
    $form['options']['surrounding']['preceded'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preceded by'),
      '#default_value' => '',
      '#maxlength' => 256,
    ];
    $form['options']['surrounding']['followed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Followed by'),
      '#default_value' => '',
      '#maxlength' => 256,
    ];

    $form['options']['message'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The below settings override the values configured in the <a href="@url" target="_blank">admin settings page</a>.', ['@url' => '/admin/config/content/scanner']),
    ];

    $form['options']['mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive search'),
      '#default_value' => $mode,
      '#description' => $this->t('Check this if the search should only return results that exactly match the capitalization of your search terms.'),
    ];
    $form['options']['wholeword'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match whole word'),
      '#default_value' => $wholeword,
      '#description' => $this->t("Check this if you don't want the search to match any partial words. For instance, if you search for 'run', a whole word search will <em>not</em> match 'running'."),
    ];
    $form['options']['regex'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use regular expressions in search'),
      '#default_value' => $regex,
      '#description' => $this->t('Check this if you want to use regular expressions in your search terms.'),
    ];
    $form['options']['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published nodes only'),
      '#default_value' => $published,
      '#description' => $this->t('Check this if you only want your search and replace to affect fields in nodes that are published.'),
    ];
    $form['options']['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Content language'),
      '#default_value' => $language,
      '#options' => $this->getLanguages(),
      '#description' => $this->t('The language of the content you would like to search through.'),
    ];

    // Display the results if any is found, then purge it so it doesn't stick
    // and confuse the UI for subsequent runs.
    $store = $this->tempStore->get('scanner');
    $results_output = $store->get('results_output');
    if (!empty($results_output)) {
      $form['results'] = $results_output;
      $op = $store->get('op');
      if ($op == $this->t('Replace')) {
        $form['results']['#title'] = $this->t('Content that was changed');
      }

      // Empty the tempstore.
      $store->delete('results');
      $store->delete('results_output');
      $store->delete('op');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation logic here.
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->cleanValues();
    $store = $this->tempStore->get('scanner');
    $op = $form_state->getUserInput()['op'];

    // Save the $form_state values into the user tempstore for later.
    foreach ($form_state->getValues() as $key => $value) {
      $store->set($key, $form_state->getValue($key));
    }

    $store->set('op', $op);

    $config = $this->configFactory->get('scanner.admin_settings');
    $fields = $config->get('fields_of_selected_content_type');

    // Build an array of batch operation jobs.
    // Batch job will need the field and the $form_state values.
    $operations = [];
    foreach ($fields as $field) {
      $operations[] = [
        '\Drupal\scanner\Form\ScannerForm::batchSearch',
        [
          $field,
          $form_state->getValues(),
        ],
      ];
    }

    $batch = [
      'title' => $this->t('Scanner Search Batch'),
      'operations' => $operations,
      'finished' => '\Drupal\scanner\Form\ScannerForm::batchFinished',
      'progress_message' => $this->t('Processed @current out of @total'),
    ];

    batch_set($batch);

    // If this is the search, reload the form after it's finished.
    if ($op == $this->t('Search')) {
      $form_state->setRebuild();
    }

    // Redirect to the confirmation page after the search batch is done.
    else {
      $form_state->setRedirect('scanner.admin_confirm');
    }
  }

  /**
   * Batch operation function.
   *
   * @param string $field
   *   The name of the field.
   * @param array $values
   *   The $form_state values.
   * @param array $context
   *   An array containing data that is persisted across batch jobs.
   *
   * @see https://api.drupal.org/api/drupal/core%21includes%21form.inc/group/batch/8.5.x
   */
  public static function batchSearch(string $field, array $values, array &$context): void {
    $pluginManager = \Drupal::service('plugin.manager.scanner');
    [$entity_type, , $field_name] = explode(':', $field);

    // Attempt to load the appropriate entity plugin.
    try {
      $plugin = $pluginManager->createInstance('scanner_' . $entity_type);
      if (!$plugin) {
        throw new PluginException('Unable to load scanner plugin for entity type ' . $entity_type . '.');
      }
      else {
        $results = $plugin->search($field, $values);
        if (!empty($results['results'])) {
          // If the entity type was not found before, add the entire results to
          // the queue.
          if (!isset($context['results'][$entity_type])) {
            $context['results'][$entity_type] = $results;
          }
          else {
            foreach ($results['results'] as $id => $result) {
              // If the entity was not found before, add the entire results to
              // the queue.
              if (!isset($context['results'][$entity_type]['results'][$id])) {
                $context['results'][$entity_type]['results'][$id] = $result;
              }
              // The entity was found before, so add this one field.
              else {
                $context['results'][$entity_type]['results'][$id]['fields'][$field_name] = $result['fields'][$field_name];
              }
            }
          }
          $context['message'] = 'Searching through field...';
        }
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
      // Persist the results in the tempstore.
      $store = \Drupal::service('tempstore.private')->get('scanner');
      $output = AdminHelper::buildResults($results);
      $store->set('results', $results);
      $store->set('results_output', $output);

      \Drupal::messenger()->addMessage(t('Found @matches matches in @entities entities.', [
        '@matches' => $output['#counts']['matches'],
        '@entities' => $output['#counts']['entities'],
      ]));
    }
    else {
      \Drupal::messenger()->addMessage(t('There were some errors.'));
    }
  }

  /**
   * Helper function to fetch languaged enabled on the site.
   *
   * @return array
   *   The languages keyed by langcode, with an option "all" for all languages.
   */
  public function getLanguages(): array {
    $languages = $this->languageManager->getLanguages();
    foreach ($languages as $language) {
      $langs[$language->getId()] = $language->getName();
    }
    $langs['all'] = $this->t('All languages');
    return $langs;
  }

}
