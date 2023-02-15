<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'office_hours_week' widget.
 *
 * @FieldWidget(
 *   id = "office_hours_list",
 *   label = @Translation("Office hours (list)"),
 *   field_types = {
 *     "office_hours",
 *   }
 * )
 */
class OfficeHoursListWidget extends OfficeHoursWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $items[$delta];
    if ($item->isExceptionDay()) {
      // @todo Enable List widget for Exception days.
      return [];
    }
    $default_value = $item->getValue();
    $day = $default_value['day'];
    $element['value'] = [
      '#day' => $day,
      // Make sure the value is shown in OfficeHoursSlot().
      '#daydelta' => 0,
      '#type' => 'office_hours_list',
      '#default_value' => $default_value,
      // Wrap all of the select elements with a fieldset.
      '#theme_wrappers' => [
        'fieldset',
      ],
      '#attributes' => [
        'class' => [
          'container-inline',
        ],
      ],

    ] + $element['value'];

    return $element;
  }

  /**
   * This function repairs the anomaly we mentioned before.
   *
   * Reformat the $values, before passing to database.
   *
   * @see formElement(), formMultipleElements().
   *
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Reformat the $values, before passing to database.
    foreach ($values as &$item) {
      $item = $item['value'];
    }
    $values = parent::massageFormValues($values, $form, $form_state);

    return $values;
  }

}
