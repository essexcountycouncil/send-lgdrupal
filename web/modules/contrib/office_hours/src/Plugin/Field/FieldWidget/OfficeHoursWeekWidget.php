<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Plugin implementation of the 'office_hours_default' widget.
 *
 * @FieldWidget(
 *   id = "office_hours_default",
 *   label = @Translation("Office hours (week)"),
 *   field_types = {
 *     "office_hours",
 *   },
 *   multiple_values = "FALSE",
 * )
 *
 * @todo Fix error with multiple OH fields with Exception days per bundle.
 */
class OfficeHoursWeekWidget extends OfficeHoursWidgetBase {

  /**
   * Special handling to create form elements for multiple values.
   *
   * Removed the added generic features for multiple fields:
   * - Number of widgets;
   * - AHAH 'add more' button;
   * - Table display and drag-n-drop value reordering.
   * N.B. This is never called with Annotation: multiple_values = "FALSE".
   *
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();
    if ($field_cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $this->fieldDefinition->getFieldStorageDefinition()
        ->setCardinality($this->getFieldSetting('cardinality_per_day') * OfficeHoursDateHelper::DAYS_PER_WEEK);
    }

    $elements = parent::formMultipleElements($items, $form, $form_state);

    // Remove the 'drag-n-drop reordering' element.
    $elements['#cardinality_multiple'] = FALSE;
    // Remove the little 'Weight for row n' box.
    unset($elements[0]['_weight']);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // In D8, we have a (deliberate) anomaly in the widget.
    // We prepare 1 widget for the whole week, but the field has unlimited cardinality.
    // So with $delta = 0, we already show ALL values.
    if ($delta > 0) {
      return [];
    }

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Create an indexed two level array of time slots:
    // - First level are day numbers.
    // - Second level contains items arranged by $day_delta.
    $items->filterEmptyItems();
    $indexed_items = array_fill_keys(range(0, 6), []);
    foreach ($items as $item) {
      $day = $item->getValue()['day'];
      $indexed_items[$day][] = $item;
    }

    // Build elements, sorted by first_day_of_week.
    $elements = [];
    $days = OfficeHoursDateHelper::weekDaysOrdered(range(0, 6));
    $cardinality = $this->getFieldSetting('cardinality_per_day');
    $field_settings = $element['#field_settings'];
    foreach ($days as $day) {
      for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
        $item = $indexed_items[$day][$day_delta] ?? $items->appendItem(['day' => $day]);
        $default_value = $item->getValue();
        $elements[] = [
          '#day' => $day,
          '#daydelta' => $day_delta,
          '#type' => 'office_hours_slot',
          '#default_value' => $default_value,
          '#field_settings' => $field_settings,
          '#date_element_type' => $this->getSetting('date_element_type'),
        ];
      }
    }

    // Build multi element widget. Copy the description, etc. into the table.
    $header = [
      'title' => $this->t(''), // or $this->t('Day of week'), // or NULL,
      'from' => $this->t('From', [], ['context' => 'A point in time']),
      'to' => $this->t('To', [], ['context' => 'A point in time']),
      'comment' => $this->t('Comment'),
      'operations' => [
        'data' => $this->t('Operations'),
      ],
    ];
    if (!$element['#field_settings']['comment']) {
      unset($header['comment']);
    }
    $element['value'] = [
      '#type' => 'office_hours_table',
      '#header' => $header,
      '#tableselect' => FALSE,
      '#tabledrag' => FALSE,
    ] + $element['value'] + $elements;

    // Wrap the table in a collapsible fieldset, which is the only way(?)
    // to show the 'required' asterisk and the help text.
    // The help text is now shown above the table, as requested by some users.
    // N.B. For some reason, the title is shown in Capitals.
    $element['#type'] = 'details';
    // Controls the HTML5 'open' attribute. Defaults to FALSE.
    $element['#open'] = TRUE;

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
    $multiple_values = $this->getPluginDefinition()['multiple_values'];
    if ($multiple_values == 'FALSE') {
      // Below line works fine with Annotation: multiple_values = "FALSE".
      $values = $values['value'];
    }
    elseif ($multiple_values == 'TRUE') {
      // Below lines should work fine with Annotation: multiple_values = "TRUE".
      $values = reset($values)['value'];
    }
    $values = parent::massageFormValues($values, $form, $form_state);

    return $values;
  }

}
