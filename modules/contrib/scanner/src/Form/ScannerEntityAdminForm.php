<?php

namespace Drupal\scanner\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\scanner\Plugin\ScannerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for choosing which entity types + fields the scanner covers.
 */
class ScannerEntityAdminForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected ScannerPluginManager $scannerPluginManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
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
      $container->get('plugin.manager.scanner'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'scanner_scope_settings';
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

    $available_entity_types = $this->getAvailableEntityTypes();
    $form['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled entity types'),
      '#options' => $available_entity_types,
      '#default_value' => $config->get('enabled_content_types') ?? [],
      '#description' => $this->t('Tick an entity type to expose its scannable fields below. Third-party plugins can be written to add more options here.'),
      '#attributes' => [
        'data-scanner-select-all' => TRUE,
        'data-scanner-select-all-screen-reader-label' => $this->t('Select all entity types'),
        'data-scanner-select-none-screen-reader-label' => $this->t('Deselect all entity types'),
      ],
      '#attached' => ['library' => ['scanner/select-all']],
      '#ajax' => [
        'callback' => [$this, 'getFieldsCallback'],
        'event' => 'change',
        'wrapper' => 'content-type-fields',
      ],
    ];

    // During an AJAX rebuild read the entity-type selection from form state;
    // otherwise fall back to saved config on the first render.
    $enabled_entity_types = $form_state->getValue('enabled_content_types')
      ?? ($config->get('enabled_content_types') ?? []);
    $enabled_field_keys = array_flip($config->get('fields_of_selected_content_type') ?? []);

    // `#tree` scopes the per-type selections so the AJAX wrapper can rebuild
    // just this container, and submit() knows which type owns which field.
    $form['fields_by_type'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="content-type-fields">',
      '#suffix' => '</div>',
    ];

    $rendered_any_group = FALSE;
    foreach ($enabled_entity_types as $entity_bundle_key => $is_on) {
      if (empty($is_on) || !isset($available_entity_types[$entity_bundle_key])) {
        continue;
      }
      $rendered_any_group = TRUE;
      $type_fields = $this->getEntityTypeFields($available_entity_types, [$entity_bundle_key => $entity_bundle_key]);

      $form['fields_by_type'][$entity_bundle_key] = [
        '#type' => 'details',
        '#title' => $available_entity_types[$entity_bundle_key],
        '#open' => FALSE,
      ];

      if (empty($type_fields)) {
        $form['fields_by_type'][$entity_bundle_key]['empty'] = [
          '#markup' => '<p><em>' . $this->t('No searchable fields are available on this bundle.') . '</em></p>',
        ];
        continue;
      }

      $form['fields_by_type'][$entity_bundle_key]['fields'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Enabled fields'),
        '#title_display' => 'invisible',
        '#options' => $type_fields,
        '#default_value' => array_keys(array_intersect_key($type_fields, $enabled_field_keys)),
        '#attributes' => [
          'data-scanner-select-all' => TRUE,
          'data-scanner-select-all-screen-reader-label' => $this->t('Select all fields for @type', ['@type' => $available_entity_types[$entity_bundle_key]]),
          'data-scanner-select-none-screen-reader-label' => $this->t('Deselect all fields for @type', ['@type' => $available_entity_types[$entity_bundle_key]]),
        ],
        '#attached' => ['library' => ['scanner/select-all']],
      ];
    }

    if (!$rendered_any_group) {
      $form['fields_by_type']['empty'] = [
        '#markup' => '<p><em>' . $this->t('Enable an entity type above to see its fields here.') . '</em></p>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Collapse the per-entity-type field selections back into the flat list
    // the rest of the module still expects.
    $selected_fields = [];
    foreach ((array) $form_state->getValue('fields_by_type', []) as $type_group) {
      if (isset($type_group['fields']) && is_array($type_group['fields'])) {
        $selected_fields = array_merge($selected_fields, array_filter($type_group['fields']));
      }
    }

    $this->config('scanner.admin_settings')
      ->set('enabled_content_types', array_filter((array) $form_state->getValue('enabled_content_types')))
      ->set('fields_of_selected_content_type', array_values($selected_fields))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * AJAX callback that returns the per-entity-type fields container.
   */
  public function getFieldsCallback(array $form, FormStateInterface $form_state): array {
    return $form['fields_by_type'];
  }

  /**
   * Gets a list of available entity types as input options.
   */
  protected function getAvailableEntityTypes(): array {
    $options = [];
    foreach ($this->scannerPluginManager->getDefinitions() as $definition) {
      $entity_type_id = $definition['type'];
      try {
        $entity_type = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType();
      }
      catch (PluginNotFoundException) {
        continue;
      }
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_id => $bundle) {
        $options["$entity_type_id:$bundle_id"] = $this->t('@entity_type » @bundle', [
          '@entity_type' => $entity_type->getLabel(),
          '@bundle' => $bundle['label'],
        ]);
      }
    }
    return $options;
  }

  /**
   * Gets a list of entity fields as input options.
   */
  protected function getEntityTypeFields(array $available_entity_types, array $entity_types): array {
    $options = [];
    foreach ($entity_types as $key => $value) {
      if (empty($value) || !isset($available_entity_types[$key])) {
        continue;
      }
      [$entity_type, $bundle] = explode(':', $key);
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->isComputed() || $field_definition->isInternal()) {
          continue;
        }
        // Only allow configurable fields (created via UI) and the essential
        // 'title' base field.
        // > Subject for comments.
        if (!$field_definition instanceof FieldConfigInterface && !in_array($field_name, ['title', 'subject'])) {
          continue;
        }

        $allowed_field_type = ['link', 'string', 'string_long', 'text', 'text_long', 'text_with_summary'];
        if (in_array($field_definition->getType(), $allowed_field_type, TRUE)) {
          if (str_starts_with($field_name, 'parent_')) {
            continue;
          }
          $name_with_type = "$entity_type:$bundle:$field_name";
          $options[$name_with_type] = $this->t('@entity_bundle » @field_label <small><strong>(@field_name)</strong></small>', [
            '@entity_bundle' => $available_entity_types[$key],
            '@field_label' => $field_definition->getLabel(),
            '@field_name' => $field_name,
          ]);
        }
      }
    }
    return $options;
  }

}
