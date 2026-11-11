<?php

namespace Drupal\scanner\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring the default scanner settings.
 */
class ScannerDefaultAdminForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'scanner_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['scanner.admin_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('scanner.admin_settings');

    $form['scanner_link'] = [
      '#markup' => $this->t('To use the scanner, go to the <a href=":url">Search and Replace Scanner</a> page.', [
        ':url' => Url::fromRoute('scanner.admin_content')->toString(),
      ]),
    ];

    $form['word_boundaries'] = [
      '#type' => 'radios',
      '#title' => $this->t('Regex word boundaries'),
      '#options' => [
        'auto' => $this->t('Detect automatically; should work for most sites'),
        'spencer' => $this->t('Henry Spencer mode - [[:<:]] and [[:>:]] - MySQL (8.0.3 and older), MariaDB (all versions)'),
        'icu' => $this->t('ICU mode - \b - MySQL (8.0.4 and newer)'),
      ],
      '#default_value' => $config->get('word_boundaries') ?? 'spencer',
      '#description' => $this->t('MariaDB and all releases of MySQL prior to 8.0.4 used the first option, whereas MySQL 8.0.4 and newer use the second option.'),
      '#required' => TRUE,
    ];

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default options'),
    ];
    $form['settings']['scanner_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default: Case sensitive search mode'),
      '#default_value' => $config->get('scanner_mode'),
    ];
    $form['settings']['scanner_wholeword'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default: Match whole word'),
      '#default_value' => $config->get('scanner_wholeword'),
    ];
    $form['settings']['scanner_regex'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default: Regular expression search'),
      '#default_value' => $config->get('scanner_regex'),
    ];
    $form['settings']['scanner_published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default: Search published nodes only'),
      '#default_value' => $config->get('scanner_published'),
    ];
    $form['settings']['scanner_pathauto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default: Maintain custom aliases'),
      '#default_value' => $config->get('scanner_pathauto'),
    ];
    $form['settings']['scanner_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug: log each compiled query'),
      '#description' => $this->t('When enabled, each search writes the composed entity query, the underlying SQL, and the PHP regex to the watchdog log under the <em>scanner</em> channel. Useful for debugging why a search returns unexpected counts. Leave off in production.'),
      '#default_value' => $config->get('scanner_debug') ?? FALSE,
    ];
    $form['settings']['scanner_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Default: Content language'),
      '#options' => $this->getLanguages(),
      '#default_value' => $config->get('scanner_language'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('scanner.admin_settings')
      ->set('scanner_mode', $form_state->getValue('scanner_mode'))
      ->set('scanner_regex', $form_state->getValue('scanner_regex'))
      ->set('scanner_wholeword', $form_state->getValue('scanner_wholeword'))
      ->set('scanner_published', $form_state->getValue('scanner_published'))
      ->set('scanner_pathauto', $form_state->getValue('scanner_pathauto'))
      ->set('scanner_debug', (bool) $form_state->getValue('scanner_debug'))
      ->set('scanner_language', $form_state->getValue('scanner_language'))
      ->set('word_boundaries', $form_state->getValue('word_boundaries'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets a list of languages as input options.
   *
   * @return array
   *   An array containing the list of enabled languages.
   */
  protected function getLanguages(): array {
    $languages = $this->languageManager->getLanguages();
    $items = [
      'all' => $this->t('All'),
    ];
    foreach ($languages as $language) {
      $items[$language->getId()] = $language->getName();
    }

    return $items;
  }

}
