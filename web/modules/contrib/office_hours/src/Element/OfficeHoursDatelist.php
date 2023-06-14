<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Datetime\Element\Datelist;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element.
 *
 * @FormElement("office_hours_datelist")
 */
class OfficeHoursDatelist extends Datelist {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $parent_info = parent::getInfo();

    $info = [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#element_validate' => [[static::class, 'validateOfficeHoursDatelist']],
      // @see Drupal\Core\Datetime\Element\Datelist.
      '#date_part_order' => ['hour', 'minute', 'ampm'],
      '#date_year_range' => '1900:2050',
      '#date_time_element' => 'time',
      // @todo Add Timezone.
      '#date_timezone' => '+0000',
    ];

    // #process, #validate bottom-up.
    $info['#element_validate'] = array_merge($parent_info['#element_validate'], $info['#element_validate']);

    return $info + $parent_info;
  }

  /**
   * {@inheritdoc}
   *
   * Callback for hours element.
   *
   * Takes #default_value and dissects it in hours, minutes and ampm indicator.
   * Mimics the date_parse() function.
   * - g = 12-hour format of an hour without leading zeros 1 through 12
   * - G = 24-hour format of an hour without leading zeros 0 through 23
   * - h = 12-hour format of an hour with leading zeros   01 through 12
   * - H = 24-hour format of an hour with leading zeros   00 through 23
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if ($input !== FALSE) {
      // Set empty minutes field to '00' for better UX.
      if (is_array($input) && $input['hour'] !== '' && $input['minute'] === '') {
        $input['minute'] = '00';
      }
      // Ensure that 'all_day' checkbox works correctly.
      // If there is no default value,
      // then we do not have any hours, so simply return NULL.
      if ($input !== NULL) {
        $input = parent::valueCallback($element, $input, $form_state);
      }
    }
    else {
      // Initial load from database.
      // Format the integer time into a DateTime object.
      $date = NULL;
      try {
        $time = $element['#default_value'];
        if (is_array($time)) {
          $date = OfficeHoursDateHelper::createFromArray($time);
        }
        elseif (is_numeric($time)) {
          $timezone = $element['#date_timezone'];
          // The Date function needs a fixed format, so format $time to '0030'.
          $time = OfficeHoursDateHelper::format($time, 'Hi');
          $date = OfficeHoursDateHelper::createFromFormat('Gi', $time, $timezone);
        }
      }
      catch (\Exception $e) {
        $date = NULL;
      }
      $element['#default_value'] = $date;

      $input = parent::valueCallback($element, $input, $form_state);
    }

    return $input;
  }

  /**
   * Process the hours element before showing it.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The screen element.
   */
  public static function processDatelist(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processDatelist($element, $form_state, $complete_form);

    $time_format = $element['#field_settings']['time_format'];
    $limit_start = $element['#field_settings']['limit_start'];
    $limit_end = $element['#field_settings']['limit_end'];

    // Get the valid, restricted hours.
    // Date API doesn't provide a straight method for this.
    $element['hour']['#options'] = OfficeHoursDateHelper::hours($time_format, FALSE, $limit_start, $limit_end);

    return $element;
  }

  /**
   * Validate the hours selector element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateOfficeHoursDatelist(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $input = $element['#value'];

    $value = '';
    if (isset($input['object']) && $input['object']) {
      $value = (string) $input['object']->format('Gi');
      // Set value for usage in OfficeHoursBaseSlot::validateOfficeHoursSlot().
      $element['#value'] = $value;
    }
    $form_state->setValueForElement($element, $value);
  }

}
