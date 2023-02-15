<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element for Exception days.
 *
 * @FormElement("office_hours_exceptions_slot")
 */
class OfficeHoursExceptionsSlot extends OfficeHoursWeekSlot {

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {

    // Update $element['#value'] with default data and prepare $element widget.
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    // Facilitate Exception day specific things, such as changing date.
    $value = $element['#value'];
    $day = $value['day'];
    $day_delta = $element['#daydelta'];
    $default_day = (is_numeric($day)) ? date('Y-m-d', $day) : '';
    $label = OfficeHoursDateHelper::getLabel('l', $value, $day_delta);

    if ($day_delta == 0) {
      // For first time slot of a day, set a 'date' select element + day name,
      // overriding the hidden (Week widget) or select (List widget) 'day'.
      // Override (hide) the 'day' select-field.
      $element['day'] = [
        '#type' => 'date',
        '#prefix' => $day_delta
          ? "<div class='office-hours-more-label'>$label</div>"
          : "<div class='office-hours-label'>$label</div>",
        '#default_value' => $default_day,
      ];
    }
    else {
      // Leave 'more slots' as-is, but overriding the value,
      // so all slots have same day number.
      $element['day'] = [
        '#type' => 'hidden',
        '#prefix' => $day_delta
          ? "<div class='office-hours-more-label'>$label</div>"
          : "<div class='office-hours-label'>$label</div>",
        '#default_value' => $default_day,
        '#value' => $default_day,
      ];

    }
    // Add 'day_delta' to facilitate ExceptionsSlot.
    // @todo This adds a loose column to the widget. Fix it, avoiding colspan.
    $element['day_delta'] = [
      '#type' => 'value', // 'hidden',
      '#value' => $day_delta,
    ];

    return $element;
  }

}
