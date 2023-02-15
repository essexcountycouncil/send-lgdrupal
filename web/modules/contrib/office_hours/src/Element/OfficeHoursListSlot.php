<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element for the List Widget.
 *
 * @FormElement("office_hours_list")
 */
class OfficeHoursListSlot extends OfficeHoursBaseSlot {

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

    // Update $element['#value'] with default data and prepare $element widget.
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    $day = $element['#value']['day'] ?? '';
    $element['day'] = [
      '#type' => 'select',
      '#options' => OfficeHoursDateHelper::weekDays(FALSE),
      '#default_value' => $day,
    ];

    return $element;
  }

}
