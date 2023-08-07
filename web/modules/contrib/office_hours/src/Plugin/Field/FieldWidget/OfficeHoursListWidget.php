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
    // On fieldSettings page admin/structure/types/manage/<TYPE>/fields/<FIELD>,
    // $delta may be 0 for an empty list, so $item does not exist.
    if (!$item || $item->isException()) {
      // @todo Enable List widget for Exception days.
      return [];
    }

    static $day_index = 0;
    $day_index++;

    $default_value = $item->getValue();
    $element['value'] = [
      '#type' => 'office_hours_list',
      '#default_value' => $default_value,
      '#day_index' => $day_index,
      '#day_delta' => 0,
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
   * @see formElement(),formMultipleElements()
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
