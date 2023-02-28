<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a one-line text field form element for the Week Widget.
 *
 * @FormElement("office_hours_slot")
 */
class OfficeHoursWeekSlot extends OfficeHoursBaseSlot {

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {

    // Update $element['#value'] with default data and prepare $element widget.
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    $value = $element['#value'];
    $day = $value['day'];
    $day_delta = $element['#daydelta'];
    $label = OfficeHoursDateHelper::getLabel('', $value, $day_delta);

    if ($day_delta == 0) {
      // This is the first slot of the day.
    }
    elseif (!OfficeHoursItem::isValueEmpty($value)) {
      // This is a following slot with contents.
      // Display the slot and display Add-link.
      $element['#attributes']['class'][] = 'office-hours-more';
    }
    else {
      // This is an empty following slot.
      // Hide the slot and add Add-link, in case shown by js.
      $element['#attributes']['class'][] = 'office-hours-hide';
      $element['#attributes']['class'][] = 'office-hours-more';
    }

    // Override (hide) the 'day' select-field.
    $element['day'] = [
      // For accessibility (a11y) screen readers, a header/title is introduced.
      '#title' => $label,
      // '#type' => 'item', // #3273363.
      '#type' => 'hidden',
      '#prefix' => $day_delta
        ? "<div class='office-hours-more-label'>$label</div>"
        : "<div class='office-hours-label'>$label</div>",
      '#default_value' => $day,
      '#value' => $day,
    ];

    $element['#attributes']['class'][] = "office-hours-day-$day";

    return $element;
  }

}
