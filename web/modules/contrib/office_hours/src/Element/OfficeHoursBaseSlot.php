<?php

namespace Drupal\office_hours\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a base class for Office Hours Slot form element.
 */
class OfficeHoursBaseSlot extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [[static::class, 'processOfficeHoursSlot']],
      '#element_validate' => [[static::class, 'validateOfficeHoursSlot']],
    ];
    return $info;
  }

  /**
   * Gets this list element's default operations.
   *
   * @param array $element
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  public static function getDefaultOperations(array $element) {
    $operations = [];

    $value = $element['#value'] ?? [];
    $day = $value['day'] ?? '';
    $day_delta = $element['#daydelta'];
    $max_delta = $element['#field_settings']['cardinality_per_day'] - 1;
    $suffix = ' ';

    // Show a 'Clear this line' js-link to each element.
    // Use text 'Remove', which has lots of translations.
    $operations['delete'] = [];
    if (!OfficeHoursItem::isValueEmpty($value)) {
      $operations['delete'] = [
        '#type' => 'link',
        '#title' => t('Remove'),
        '#weight' => 12,
        '#url' => Url::fromRoute('<front>'), // Dummy, will be catch-ed by js.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-delete-link', 'office-hours-link'],
        ],
      ];
    }

    // Add 'Copy' link to first slot of each day.
    // First day copies from last day.
    $operations['copy'] = [];
    if ($day_delta == 0) {
      $operations['copy'] = [
        '#type' => 'link',
        '#title' => ($day !== OfficeHoursDateHelper::getFirstDay())
          ? t('Copy previous day') : t('Copy last day'),
        '#weight' => 16,
        '#url' => Url::fromRoute('<front>'), // Dummy, will be catch-ed by js.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-copy-link', 'office-hours-link'],
        ],
      ];
    }

    // Add 'Add time slot' link to all-but-last slots of each day.
    $operations['add'] = [];
    if ($day_delta < $max_delta) {
      $operations['add'] = [
        '#type' => 'link',
        '#title' => t('Add @type', ['@type' => t('time slot')]),
        '#weight' => 11,
        '#url' => Url::fromRoute('<front>'), // Dummy, will be catch-ed by js.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-add-link', 'office-hours-link'],
        ],
      ];
    }

    return $operations;
  }

  /**
   * Render API callback: Builds one OH-slot element.
   *
   * Build the form element. When creating a form using Form API #process,
   * note that $element['#value'] is already set.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return array
   *   The enriched element, identical to first parameter.
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    $field_settings = $element['#field_settings'];

    // Massage, normalize value after pressing Form button.
    // $element is updated via reference.
    $value = OfficeHoursItem::formatValue($element['#value']);

    // Prepare $element['#value'] for Form element/Widget.
    $element['day'] = [];
    $element['starthours'] = [
      '#type' => $field_settings['element_type'], // datelist, datetime.
      '#field_settings' => $field_settings,
      // Get the valid, restricted hours. Date API doesn't provide a straight method for this.
      '#hour_options' => OfficeHoursDateHelper::hours($field_settings['time_format'], FALSE, $field_settings['limit_start'], $field_settings['limit_end']),
      // Attributes for element \Drupal\Core\Datetime\Element\Datelist - Start.
      '#date_part_order' =>
        (in_array($field_settings['time_format'], ['g', 'h']))
        ? ['hour', 'minute', 'ampm']
        : ['hour', 'minute'],
      '#date_increment' => $field_settings['increment'],
      '#date_time_element' => 'time',
      '#date_time_format' => OfficeHoursDateHelper::getTimeFormat($field_settings['time_format']),
      '#date_timezone' => '+0000',
      // Attributes for element \Drupal\Core\Datetime\Element\Datelist - End.
    ];
    $element['endhours'] = $element['starthours'];
    $element['starthours']['#default_value'] = $value['starthours'];
    $element['endhours']['#default_value'] = $value['endhours'];
    $element['comment'] = !$field_settings['comment'] ? NULL : [
      '#type' => 'textfield',
      '#default_value' => $value['comment'],
      '#size' => 20,
      '#maxlength' => 255,
      '#field_settings' => $field_settings,
    ];

    // Copied from EntityListBuilder::buildOperations().
    $element['operations'] = [
      'data' => self::getDefaultOperations($element),
    ];

    $element['#attributes']['class'][] = 'form-item';
    $element['#attributes']['class'][] = 'office-hours-slot';

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
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   */
  public static function validateOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    $error_text = '';

    // Return an array with starthours, endhours, comment.
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    $input_exists = TRUE;

    // Avoid complex validation below. Remove comment, only in validation.
    $input['comment'] = NULL;
    // No complex validation if empty.
    if (OfficeHoursItem::isValueEmpty($input)) {
      return;
    }

    OfficeHoursItem::formatValue($input);

    // Exception: end time 00:00 --> 24:00.
    $start = OfficeHoursDateHelper::format($input['starthours'], 'Hi', FALSE);
    $end = OfficeHoursDateHelper::format($input['endhours'], 'Hi', TRUE);

    $field_settings = $element['#field_settings'];
    $validate_hours = $field_settings['valhrs'];
    $limit_start = $field_settings['limit_start'];
    $limit_end = $field_settings['limit_end'];
    // If any field of slot is filled, check for required time fields.
    $required_start = $validate_hours || $field_settings['required_start'] ?? FALSE;
    $required_end = $validate_hours || $field_settings['required_end'] ?? FALSE;

    if ($required_start && empty($start)) {
      $error_text = 'Opening hours must be set.';
      $erroneous_element = &$element['starthours'];
    }
    elseif ($required_end && empty($end)) {
      $error_text = 'Closing hours must be set.';
      $erroneous_element = &$element['endhours'];
    }
    elseif ($validate_hours) {
      // Both Start and End must be entered. That is validated above already.
      if ($end < $start) {
        $error_text = 'Closing hours are earlier than Opening hours.';
        $erroneous_element = &$element;
      }
      elseif ((!empty($limit_start) || !empty($limit_end))) {
        $limit_start = OfficeHoursDateHelper::format($field_settings['limit_start'] * 100, 'Hi', TRUE);
        $limit_end = OfficeHoursDateHelper::format($field_settings['limit_end'] * 100, 'Hi', TRUE);
        if ($start && ($limit_start > $start)
          || ($end && ($limit_end < $end))) {
          $error_text = 'Hours are outside limits ( @start - @end ).';
          $erroneous_element = &$element;
        }
      }
    }

    if ($error_text) {
      $label = OfficeHoursDateHelper::getLabel('long', $input);
      $error_text = $label
        . ': '
        . t($error_text,
          [
            '@start' => $limit_start . ':00',
            '@end' => $limit_end . ':00',
          ],
          ['context' => 'office_hours']
        );
      $form_state->setError($erroneous_element, $error_text);
    }
  }

}
