<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element for Exception days.
 *
 * @FormElement("office_hours_exceptions_slot")
 */
class OfficeHoursExceptionsSlot extends OfficeHoursListSlot {

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {

    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    // The valueCallback() has populated the #value array.
    $value = $element['#value'];
    $day = $element['#value']['day'];
    $day_delta = $element['#day_delta'];
    $label = parent::getLabel('l', $value, $day_delta);

    // Override the hidden (Week widget) or select (List widget)
    // first time slot 'day', setting a 'date' select element + day name.
    $element['day'] = [
      '#type' => $day_delta ? 'hidden' : 'date',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => 'The exception day',
      '#title_display' => 'invisible',
      '#prefix' => $day_delta
        ? "<div class='office-hours-more-label'>$label</div>"
        : "<div class='office-hours-label'>$label</div>",
      '#default_value' => $day_delta
        // Add 'day_delta' to facilitate changing and closing Exception days.
        ? 'exception_day_delta'
        // Format the numeric day number to Y-m-d format for the widget.
        : (is_numeric($day) ? date(OfficeHoursDateHelper::DATE_STORAGE_FORMAT, $day) : ''),
    ];
    if (isset($element['all_day'])) {
      $element['all_day'] = [
        '#type' => $day_delta ? 'hidden' : 'checkbox',
        '#default_value' => $value['all_day'],
      ];
    }
    if (isset($element['endhours'])) {
      unset($element['endhours']['#prefix']);
    }

    return $element;
  }

}
