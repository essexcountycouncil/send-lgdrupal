<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Plugin implementation of the 'office_hours_exceptions' widget.
 *
 * @FieldWidget(
 *   id = "office_hours_exceptions_only",
 *   label = @Translation("Office hours exceptions (list)"),
 *   field_types = {
 *     "office_hours_exceptions",
 *   },
 *   multiple_values = "FALSE",
 * )
 *
 * @todo Fix error with multiple OH fields with Exception days per bundle.
 */
class OfficeHoursExceptionsWidget extends OfficeHoursListWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // In D8, we have a (deliberate) anomaly in the widget.
    // We prepare 1 widget for the whole week,
    // but the field has unlimited cardinality.
    // So with $delta = 0, we already show ALL values.
    if ($delta > 0) {
      return [];
    }

    // Add a helper for JS links (e.g., copy-link previousSelector) in widget.
    static $day_index = 0;

    // Make form_state not cached since we will update it in ajax callback.
    $form_state->setCached(FALSE);

    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $items->filterEmptyItems();

    // Create an indexed two level array of time slots:
    // - First level are day numbers.
    // - Second level contains field values arranged by $day_delta.
    $indexed_items = [];
    foreach ($items as $item) {
      // Skip Weekdays.
      if ($item->isException()) {
        $day = $item->getValue()['day'];
        $indexed_items[$day][] = $item;
      }
    }
    $indexed_items = OfficeHoursDateHelper::weekDaysOrdered($indexed_items);

    $field_name = $this->fieldDefinition->getName();
    // Add more days if we clicked "Add exception".
    $count = $form_state->get('exceptions_count_' . $field_name)
      ?? count($indexed_items);
    $form_state->set('exceptions_count_' . $field_name, $count);

    for ($i = count($indexed_items); $i < $count; $i++) {
      $indexed_items[] = [
        0 => $items->appendItem([
          'day' => OfficeHoursItem::EXCEPTION_DAY,
        ]),
      ];
    }

    // Build elements, sorted by day number/timestamp.
    $elements = [];
    $cardinality = $this->getFieldSetting('cardinality_per_day');
    foreach ($indexed_items as $day => $indexed_item) {
      $day_index++;

      for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
        $item = $indexed_items[$day][$day_delta] ?? $items->appendItem([
          'day' => OfficeHoursItem::EXCEPTION_DAY,
        ]);
        $default_value = $item->getValue();
        $day = $default_value['day'];
        $elements[] = [
          '#type' => 'office_hours_exceptions_slot',
          '#default_value' => $default_value,
          '#day_index' => $day_index,
          '#day_delta' => $day_delta,
          // Add field settings, for usage in each Element.
          '#field_settings' => $this->getFieldSettings(),
          '#date_element_type' => $this->getSetting('date_element_type'),
        ];
      }
    }

    // @todo Use same text label as Field Formatter settings.
    $label = $this->t('Exceptions');
    $element = [
      '#type' => 'details',
      '#open' => !$this->getSetting('collapsed'),
      '#title' => $label,
      '#field_name' => $field_name,
      '#field_parents' => $form['#parents'],
    ] + $element;

    // Build multi element widget. Copy the description, etc. into the table.
    // Use the more complex 'data' construct,
    // to allow ExceptionsWeekWidget to add a 'colspan':
    $header = OfficeHoursItem::getPropertyLabels('data', $this->getFieldSettings());

    $element['value'] = [
      '#type' => 'office_hours_table',
      '#header' => $header,
      '#tableselect' => FALSE,
      '#tabledrag' => FALSE,
    ];
    // Change header 'Day' to 'Date'.
    $element['value']['#header']['day'] = $this->t('Date');
    $element['value']['#attributes'] = ['id' => 'exceptions-container'];
    $element['value']['#empty'] = $this->t('No exception day maintained, yet.');
    $element['value'] += $elements;

    $element['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add exception'),
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => 'exceptions-container',
        'effect' => 'fade',
      ],
      // No form validation when this button is clicked.
      // E.g., when the Field is required, and no Weekday items exist, yet.
      '#limit_validation_errors' => [],
      '#submit' => [
        [static::class, 'addMoreSubmit'],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];

    // Increment the items count.
    $count = $form_state->get('exceptions_count_' . $field_name);
    $count++;
    $form_state->set('exceptions_count_' . $field_name, $count);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    return $element['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * Note: this is a static version of massageFormValues, used from the
   * 'Widget of widgets' OfficeHoursComplexWeekWidget,
   * since current widget is a ListWidget, not a WeekWidget subclass.
   */
  public static function _massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = $values['value'];

    if (!is_array($values)) {
      return $values = [];
    }

    // Only need to widget specific massaging of form values,
    // All logical changes will be done in ItemList->setValue($values),
    // where the formatValue() function will be called, also.
    foreach ($values as &$value) {
      OfficeHoursItem::formatValue($value);
    }
    return $values;
  }

}
