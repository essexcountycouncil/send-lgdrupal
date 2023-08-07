<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursSeason;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a one-line form element for Season header.
 *
 * @FormElement("office_hours_season_header")
 */
class OfficeHoursSeasonHeader extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [[static::class, 'process']],
      '#value_callback' => [[static::class, 'valueCallback']],
      '#element_validate' => [[static::class, 'validate']],
    ];

    return $info;
  }

  /**
   * Render API callback: Builds one OH-slot element.
   *
   * Build the form element. When creating a form using Form API #process,
   * note that $element['#value'] is already set.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The enriched element, identical to first parameter.
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form) {

    // The valueCallback() has populated the #value array.
    $value = $element['#season'];
    /** @var \Drupal\office_hours\OfficeHoursSeason $season */
    $season = $value;
    $season_id = $season->id();

    $field_settings = $element['#field_settings'];

    // Get default column labels.
    $labels = OfficeHoursItem::getPropertyLabels('data', $field_settings + ['season' => TRUE]);

    // @todo Perhaps provide extra details following elements.
    // details #description;
    // container #description;
    // container #prefix;
    // container #title;
    // name #prefix;
    $label = $season->label();

    // Prepare $element['#value'] for Form element/Widget.
    $element['day'] = [];
    $element['id'] = [
      '#type' => 'value', // 'hidden',
      '#value' => $season_id,
    ];
    $element['name'] = [
      '#type' => 'textfield',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => $labels['season']['data'],
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#prefix' => "<b>" . $labels['season']['data'] . "</b>",
      '#default_value' => $label,
      '#size' => 16,
      '#maxlength' => 40,
    ];
    $element['from'] = [
      '#type' => 'date',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => $labels['from']['data'],
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#prefix' => "<b>" . $labels['from']['data'] . "</b>",
      '#default_value' => $season->getFromDate(OfficeHoursDateHelper::DATE_STORAGE_FORMAT),
      // @todo Add conditionally required from/to fields.
      '#required' => [
        // ':input[name="name"]' => ['value' => t('Season name')],
        // 'or',
        // ':input[name="'.$input_name.'"]' => ['value' => ''],
        // ':input[name="$input_name"]' => ['size' => '16'],
       ],
    ];
    $element['to'] = [
      '#type' => 'date',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => $labels['to']['data'],
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#prefix' => "<b>" . $labels['to']['data'] . "</b>",
      '#default_value' => $season->getToDate(OfficeHoursDateHelper::DATE_STORAGE_FORMAT),
    ];

    // @todo Add seasonal add, copy, delete links.
    /*
    // Copied from EntityListBuilder::buildOperations().
    $dummy_element = [
    '#value' => $season->toArray() + ['day' => 0, 'day_delta' => 1],
    '#day_delta' => 0,
    '#field_settings' =>$field_settings,
    ];
    $element['operations'] = [
    'data' => OfficeHoursBaseSlot::getDefaultOperations($dummy_element),
    ];
     */

    $element['#attributes']['class'][] = 'form-item';
    $element['#attributes']['class'][] = 'office-hours-slot';

    $element['#attributes']['id'] = $element['#id'];

    return $element;
  }

  /**
   * Render API callback: Validates one OH-slot element.
   *
   * Implements a callback for _office_hours_elements().
   *
   * For 'office_hours_slot' (day) and 'office_hours_datelist' (hour) elements.
   * You can find the value in $element['#value'],
   * but better in $form_state['values'],
   * which is set in validateOfficeHoursSlot().
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $error_text = '';

    // Return an array with starthours, endhours, comment.
    // Do not use NestedArray::getValue();
    // It does not return formatted values from valueCallback().
    // The valueCallback() has populated the #value array.
    $input = $element['#value'];
    if (empty($input)) {
      // Empty season dates will be cleared later.
      return;
    }
    $name = $input['name'];
    if ($name == '' || $name == t(OfficeHoursSeason::SEASON_DEFAULT_NAME)) {
      // Empty season dates will be cleared later.
      return;
    }

    $start = $input['from'];
    $end = $input['to'];
    if (empty($start)) {
      $error_text = 'A starting date must be set for the season.';
      $erroneous_element = &$element['from'];
    }
    elseif (empty($end)) {
      $error_text = 'An end date must be set for the season.';
      $erroneous_element = &$element['to'];
    }
    elseif ($end < $start) {
      // Both Start and End must be entered. That is validated above already.
      $error_text = 'Seasonal End date must be later then starting date.';
      $erroneous_element = &$element;
    }

    if ($error_text) {
      $error_text = t($error_text);
      $form_state->setError($erroneous_element, $error_text);
    }
  }

  /**
   * Returns the translated label of a Weekday/Exception day, e.g., 'tuesday'.
   *
   * @param string $pattern
   *   The day/date formatting pattern.
   * @param array $value
   *   An Office hours value structure.
   * @param int $day_delta
   *   An optional day_delta.
   *
   * @return bool|string
   *   The formatted day label, e.g., 'tuesday'.
   */
  public static function getLabel(string $pattern, array $value, $day_delta = 0) {
    $label = OfficeHoursDateHelper::getLabel($pattern, $value, $day_delta);
    return $label;
  }

  /**
   * Determines whether the data structure is empty.
   *
   * @return bool
   *   TRUE if the data structure is empty, FALSE otherwise.
   */
  public static function isEmpty($value) {
    return OfficeHoursItem::isValueEmpty($value);
  }

}
