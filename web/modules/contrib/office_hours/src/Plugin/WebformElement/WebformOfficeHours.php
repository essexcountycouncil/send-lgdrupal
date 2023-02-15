<?php

namespace Drupal\office_hours\Plugin\WebformElement;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\office_hours\Element\OfficeHoursBaseSlot;
use Drupal\office_hours\Plugin\Field\FieldFormatter\OfficeHoursFormatterBase;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'office_hours' element.
 *
 * @WebformElement(
 *   id = "office_hours",
 *   label = @Translation("Office hours"),
 *   description = @Translation("Defines a 'weekly office hours' webform element"),
 *   category = @Translation("Composite elements"),
 *   composite = TRUE,
 *   multiple = FALSE,
 *   multiline = TRUE,
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "office_hours",
 *   },
 * )
 *
 * @see \Drupal\office_hours\Element\OfficeHours
 *
 * @todo Fix support for 'required' attribute in Widget.
 * @todo Fix help text, which is now not visible in Widget.
 */
class WebformOfficeHours extends WebformCompositeBase {

  /**
   * Static field definitions.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected $fieldDefinitions = [];

  /**
   * {@inheritdoc}
   *
   * Copied from office_hours\...\OfficeHoursItem\defaultStorageSettings().
   */
  protected function defineDefaultProperties() {
    $formatter_default_settings = OfficeHoursFormatterBase::defaultSettings();
    $widget_default_settings = OfficeHoursItem::defaultStorageSettings();
    $properties = $widget_default_settings
      + $formatter_default_settings
      + parent::defineDefaultProperties();

    unset($properties['multiple__header']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * Copied from office_hours\...\OfficeHoursItem\storageSettingsForm().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Get field overrider from element properties.
    $element_properties = $form_state->get('element_properties');
    $form['office_hours'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Office hours settings'),
    ] + OfficeHoursItem::getStorageSettingsElement($element_properties);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [];
  }

  /**
   * {@inheritdoc}
   *
   * Copied from office_hours\...\OfficeHoursWeekWidget\formElement().
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // If the element is not properly defined, do not show the formatter/widget.
    if (!isset($element['#webform_key'])) {
      return;
    }
    $widget = $this->getWebformOfficeHoursPlugin(
      'plugin.manager.field.widget',
      'office_hours_default',
      $element
    );

    // Convert values to ItemList, in order to use Widget.
    $office_hours = $element['#default_value'] ?? [];
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $this->unserialize($office_hours, $element);
    /** @var \Drupal\Core\Field\WidgetInterface $widget */

    $form = [];
    $form_state = new FormState();
    $element = $widget->formElement($items, 0, $element, $form, $form_state);
    $element[$element['#webform_key']] = $element['value'];
    unset($element['value']);

    // @todo Webform #title display defaults to invisible.
    // $element['#title_display'] = 'invisible';
    // @todo Attach below library in Twig template.
    // @see https://www.drupal.org/node/2456753.
    // @see https://www.codimth.com/blog/web/drupal/attaching-library-pages-drupal-8 .
    $element['#attached']['library'][] = 'office_hours/office_hours_webform';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validateOfficeHoursSlot'];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementPreRenderCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementPreRenderCallbacks($element, $webform_submission);
    // Replace 'form_element' theme wrapper with composite form element.
    // @see \Drupal\Core\Render\Element\PasswordConfirm
    $element['#pre_render'] = [
      [get_called_class(), 'preRenderWebformCompositeFormElement'],
    ];
  }

  /**
   * Form API callback for Widget.
   */
  public static function validateOfficeHoursSlot(array &$element, FormStateInterface $form_state, array &$completed_form) {

    OfficeHoursBaseSlot::validateOfficeHoursSlot($element, $form_state, $completed_form);

    // Encode Values here always, since this is expected by prepare(),
    // and since postLoad() does not have the values, yet,
    // so we cannot use preSave()unconditionally.
    // There does not seem to exist a Webform equivalent of MassageFormValues().
    // $errors = $form_state->getErrors($element); if ($errors !== []) {}.
    $office_hours = $form_state->getValue($element['#webform_key']);
    $office_hours = self::serialize($office_hours);
    $form_state->setValueForElement($element, $office_hours);
  }

  /**
   * {@inheritdoc}
   *
   * Copied from office_hours\...\OfficeHoursFormatterDefault\viewElements().
   *
   * @todo Allow to changgetWebformOfficeHoursPlugine the Formatter settings via UI.
   */
  protected function viewElements(array $element, WebformSubmissionInterface $webform_submission, array $options) {
    $elements = [];

    // If the element is not properly defined, do not show the formatter/widget.
    if (!isset($element['#webform_key'])) {
      return $elements;
    }

    // Convert values to ItemList, in order to use Widget.
    $office_hours = $this->getValue($element, $webform_submission, $options);
    $item_list = $this->unserialize($office_hours, $element);

    // If no data is filled for this entity, do not show the formatter.
    if (!$item_list->getValue()) {
      return $elements;
    }

    /** @var \Drupal\office_hours\Plugin\Field\FieldFormatter\OfficeHoursFormatterBase $formatter */
    $formatter = $this->getWebformOfficeHoursPlugin(
      'plugin.manager.field.formatter',
      'office_hours',
      $element
    );

    // @todo Add configurable $langcode to Formatter.
    $langcode = NULL;
    $elements = $formatter->viewElements($item_list, $langcode);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      return $this->viewElements($element, $webform_submission, $options);
    }
    else {
      return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      $build = $this->viewElements($element, $webform_submission, $options);
      $html = \Drupal::service('renderer')->renderPlain($build);
      return trim(MailFormatHelper::htmlToText($html));
    }
    else {
      return parent::formatTextItem($element, $webform_submission, $options);
    }
  }

  /**
   * Wrapper for easier code reuse from widget, formatter.
   *
   * {@inheritdoc}
   */
  protected function getSettings(array $element) {
    static $field_settings = NULL;
    if (!isset($field_settings)) {
      // Return Widget settings, reading keys from existing field.
      $formatter_default_settings = OfficeHoursFormatterBase::defaultSettings();
      $widget_default_settings = OfficeHoursItem::defaultStorageSettings();
      $settings = $widget_default_settings + $formatter_default_settings;
      foreach ($settings as $key => $value) {
        $field_settings[$key] = $this->getElementProperty($element, $key);
      }
    }
    return $field_settings;
  }

  /**
   * Gets the field definition of a render/form element.
   *
   * @param array $element
   *   The element.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   An Office Hours field definition.
   */
  protected function getFieldDefinition(array $element) {
    $field_name = $element['#webform_key'] ?? '';
    if ($field_name && !isset($this->fieldDefinitions[$field_name])) {
      $field_type = $element['#type'];
      $this->fieldDefinitions[$field_name] = BaseFieldDefinition::create($field_type)
        ->setName($field_name)
        ->setSettings($this->getSettings($element));
    }
    return $this->fieldDefinitions[$field_name] ?? NULL;
  }

  /**
   * Instantiate the widget/formatter object from the stored properties.
   *
   * @param string $service_id
   *   The ID of the service to retrieve.
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param array $element
   *   An element.
   */
  protected function getWebformOfficeHoursPlugin($service_id, $plugin_id, array $element) {
    $field_definition = $this->getFieldDefinition($element);
    if (!$field_definition) {
      return NULL;
    }

    $settings = $this->getSettings($element);
    $pluginManager = \Drupal::service($service_id);
    return $pluginManager->getInstance([
      'field_definition' => $field_definition,
      'form_mode' => $this->originalMode,
      'view_mode' => $this->viewMode,
      // No need to prepare, defaults have been merged in setComponent().
      'prepare' => FALSE,
      'configuration' => [
        'type' => $plugin_id,
        'field_definition' => $field_definition,
        'view_mode' => $this->originalMode,
        'label' => $this->label,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'settings' => $settings,
        'third_party_settings' => [],
      ],
    ]);
  }

  /**
   * Encodes Office Hours array to serialized string.
   *
   * Convert Office Hours array to serialized string,
   * since Webform does not support sub-sub components.
   *
   * @param array $office_hours
   *   Array of time slots.
   *
   * @return array
   *   Converted array of Office Hours time slots.
   */
  protected static function serialize(array $office_hours) {
    // Static function, because called from static function.
    $result = [];

    foreach ($office_hours as $key => $value) {
      if (!OfficeHoursItem::isValueEmpty($value)) {
        // Convert Office Hours from array to serialized string.
        $result[$key] = \Drupal::service('serialization.phpserialize')
          ->encode($value);
      }
    }
    return $result;

  }

  /**
   * Decodes Office Hours from serialized strings to ItemList.
   *
   * Convert Office Hours array to serialized string,
   * since Webform does not support sub-sub components.
   *
   * @param array $office_hours
   *   Array of time slots.
   * @param array $element
   *   An element.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface
   *   An Item list fo office hours.
   */
  protected function unserialize(array $office_hours, array $element) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = \Drupal::typedDataManager()
      ->create($this->getFieldDefinition($element));

    foreach ($office_hours as $key => $value) {
      $value = \Drupal::service('serialization.phpserialize')
        ->decode($value);
      $items->appendItem($value);
      $items->filterEmptyItems();
    }
    return $items;
  }

}
