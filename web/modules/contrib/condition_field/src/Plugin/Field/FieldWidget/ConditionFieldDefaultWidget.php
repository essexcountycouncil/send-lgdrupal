<?php

namespace Drupal\condition_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\condition_field\Plugin\Field\FieldType\ConditionFieldItem;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'condition_field_default' widget.
 *
 * @FieldWidget(
 *   id = "condition_field_default",
 *   label = @Translation("Conditions"),
 *   field_types = {
 *     "condition_field"
 *   }
 * )
 */
class ConditionFieldDefaultWidget extends WidgetBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ExecutableManagerInterface $manager, ContextRepositoryInterface $context_repository, LanguageManagerInterface $language) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->manager = $manager;
    $this->contextRepository = $context_repository;
    $this->language = $language;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  /*public static function defaultSettings() {
  return [
  // @todo condition plugins.
  ] + parent::defaultSettings();
  }*/

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    // @todo condition plugin checkboxes.
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    // @todo show selected condition plugins.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());
    // Drupal\condition_field\Plugin\Field\FieldType\ConditionFieldItem.
    $value = $items->get($delta)->getValue();
    $conditions = $value['conditions'] ?? [];
    $element['conditions'] = $this->buildVisibilityInterface([], $form_state, $conditions);
    // @todo .
    /*'#element_validate' => [
    [$this, 'validate'],
    ],*/

    return $element;
  }

  /**
   * Helper function for building the visibility UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $condition_config
   *   An associative array containing the saved values.
   *
   * @return array
   *   The form array with the visibility UI added in.
   *
   * @see \Drupal\block\BlockForm::buildVisibilityInterface()
   */
  protected function buildVisibilityInterface(array $form, FormStateInterface $form_state, array $condition_config = []) {
    $enabled_plugins = $this->fieldDefinition->getSetting('enabled_plugins');
    // Unique name to support multiple instances on tha same page.
    // @todo use something nicer here for unique field names.
    $group_name = Html::getUniqueId('visibility_tabs');
    // @todo Show "Not restricted" and other info on vertical tabs.
    $form[$group_name] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#parents' => [$group_name],
      '#attached' => [
        'library' => [
          'block/drupal.block',
        ],
      ],
    ];
    // @todo use field settings.
    $skip_condition_ids = ConditionFieldItem::SKIP_CONDITION_IDS;
    if (!$this->language->isMultilingual()) {
      // Don't display the language condition until we have multiple languages.
      $skip_condition_ids[] = 'language';
    }
    foreach ($this->manager->getDefinitionsForContexts($form_state->getTemporaryValue('gathered_contexts')) as $condition_id => $definition) {
      if (!isset($enabled_plugins[$condition_id]) || $enabled_plugins[$condition_id] === FALSE || in_array($condition_id, $skip_condition_ids)) {
        continue;
      }
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->manager->createInstance($condition_id, $condition_config[$condition_id] ?? []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = $group_name;
      $form[$condition_id] = $condition_form;
    }
    if (isset($form['user_role'])) {
      $form['user_role']['#title'] = $this->t('Roles');
      unset($form['user_role']['roles']['#description']);
      $form['user_role']['negate']['#type'] = 'value';
      $form['user_role']['negate']['#value'] = $form['user_role']['negate']['#default_value'];
    }
    if (isset($form['request_path'])) {
      $form['request_path']['#title'] = $this->t('Pages');
      $form['request_path']['negate']['#type'] = 'radios';
      $form['request_path']['negate']['#default_value'] = (int) $form['request_path']['negate']['#default_value'];
      $form['request_path']['negate']['#title_display'] = 'invisible';
      $form['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }
    if (isset($form['language'])) {
      $form['language']['negate']['#type'] = 'value';
      $form['language']['negate']['#value'] = $form['language']['negate']['#default_value'];
    }

    return $form;
  }

  // @todo see Drupal\block\BlockForm::validateForm()
}
