<?php

namespace Drupal\condition_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\DiffArray;
use Drupal\condition_field\Plugin\ConditionFieldData;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'condition_field' field type.
 *
 * @FieldType(
 *   id = "condition_field",
 *   label = @Translation("Condition plugin field"),
 *   description = @Translation("This field stores condition plugin settings in the database."),
 *   default_widget = "condition_field_default",
 *   default_formatter = "condition_field_string"
 * )
 */
class ConditionFieldItem extends FieldItemBase {
  const SKIP_CONDITION_IDS = [
    'entity_bundle:webform_submission',
    'node_type',
    'current_theme',
    // Webform throws js errors in browser console (missing libraries)
    // and Notice: "Uninitialized string offset: 0" on display.
    'webform',
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'enabled_plugins' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['conditions'] = MapDataDefinition::create()
      ->setLabel(new TranslatableMarkup('Conditions'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    // @todo set up proper storage.
    $schema = [
      'columns' => [
        'conditions' => [
          'type' => 'blob',
          'size' => 'normal',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'conditions';
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('gathered_contexts', \Drupal::service('context.repository')->getAvailableContexts());

    $element = [];
    $condition_plugins = [];
    $enabled_plugins = [];
    $settings = $this->getSettings();

    /** @var \Drupal\Core\Condition\ConditionManager $manager */
    $manager = \Drupal::service('plugin.manager.condition');
    foreach ($manager->getDefinitionsForContexts($form_state->getTemporaryValue('gathered_contexts')) as $condition_id => $definition) {
      if (in_array($condition_id, self::SKIP_CONDITION_IDS)) {
        continue;
      }
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $manager->createInstance($condition_id, []);

      $condition_plugins[$condition_id] = $condition->getPluginDefinition()['label'];

      $condition_enabled = $settings['enabled_plugins'][$condition_id] ?? FALSE;
      if (in_array($condition_id, $settings['enabled_plugins']) && $condition_enabled) {
        $enabled_plugins[] = $condition_id;
      }
    }

    $element['enabled_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled plugins'),
      '#options' => $condition_plugins,
      '#default_value' => $enabled_plugins,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    // @todo Typed data plugins cannot receive injected dependencies, see
    // core example: Drupal\path\Plugin\Field\FieldType\PathItem
    // https://www.drupal.org/node/2053415.
    // $constraint_manager = \Drupal::typedDataManager()
    // ->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    // @todo add validation constraints.
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['conditions'] = new ConditionFieldData($field_definition);
    // @todo set some random values.
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (is_array($values)) {
      $properties = $this->getProperties();
      $property_keys = array_keys($properties);

      $value_keys = array_keys($values);

      if (empty(array_intersect($property_keys, $value_keys))) {
        $values = [
          static::mainPropertyName() => $values,
        ];
      }
    }

    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    // $this->values['conditions'] contains all the condition configurations.
    $condition_configurations = $this->get('conditions')->getValue();
    $this->set('conditions',
      $this->retrieveConditionConfiguration($condition_configurations)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $condition_configurations = $this->get('conditions')->getValue();
    return empty(
      $this->retrieveConditionConfiguration($condition_configurations)
    );
  }

  /**
   * Gets non-empty condition configuration values.
   *
   * @param array $config_values
   *   All the values from the form.
   *
   * @return array
   *   Non-empty configuration values.
   */
  protected function retrieveConditionConfiguration(array $config_values) : array {
    $manager = \Drupal::service('plugin.manager.condition');
    $condition_configurations = [];
    foreach ($config_values as $condition_id => $values) {
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $manager->createInstance($condition_id, $values);
      $changed_config = FALSE;
      // Look for configuration settings.
      $config = $condition->getConfiguration();
      $default_config = $condition->defaultConfiguration();
      // Don't save negate without any other configuration.
      unset($default_config['negate']);
      foreach ($default_config as $key => $default_value) {
        if (is_array($default_value)) {
          // Skip unselected values.
          $current_value = array_filter($config[$key]);
          $diff = DiffArray::diffAssocRecursive($current_value, $default_value);
          $changed_config = !empty($diff);
        }
        elseif ($default_value != $config[$key]) {
          $changed_config = TRUE;
        }
        if ($changed_config) {
          break;
        }
      }
      if ($changed_config) {
        // Save this condition.
        $condition_configurations[$condition_id] = $values;
      }
    }

    if (!empty($condition_configurations)) {
      $callback = function (&$value) {
        if (!is_array($value) && is_numeric($value)) {
          $value = (string) $value;
        }
      };
      array_walk_recursive($condition_configurations, $callback);
    }

    return $condition_configurations;
  }

}
