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
 *   id = "office_hours_exceptions",
 *   label = @Translation("Office hours (week) with exceptions"),
 *   field_types = {
 *     "office_hours",
 *   },
 *   multiple_values = "FALSE",
 * )
 */
class OfficeHoursExceptionsWeekWidget extends OfficeHoursWeekWidget {

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

    // First, create a Week widget for the normal weekdays.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $items->filterEmptyItems();

    // Then, add a List Widget for the Exception days.
    // Create an indexed two level array of time slots:
    // - First level are day numbers.
    // - Second level contains field values arranged by $day_delta.
    $indexed_items = [];
    foreach ($items as $item) {
      // Skip Weekdays.
      if ($item->isExceptionDay()) {
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
      $indexed_items[] = [0 => $items->appendItem()];
    }

    // Build elements, sorted by day number/timestamp.
    $elements = [];
    $cardinality = $this->getFieldSetting('cardinality_per_day');
    $field_settings = $element['#field_settings'];
    foreach ($indexed_items as $day => $indexed_item) {
      for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
        $item = $indexed_items[$day][$day_delta] ?? $items->appendItem(['day' => $day]);
        $default_value = $item->getValue();
        $elements[] = [
          '#day' => $day,
          '#daydelta' => $day_delta,
          '#type' => 'office_hours_exceptions_slot',
          '#default_value' => $default_value,
          '#field_settings' => $field_settings,
          '#date_element_type' => $this->getSetting('date_element_type'),
        ];
      }
    }

    // @todo Use same text label as Field Formatter settings.
    $label = $this->t('Exceptions');
    $element['exceptions'] = [
      '#type' => 'container',
      '#field_name' => $field_name,
      '#field_parents' => $form['#parents'],
      '#prefix' => "<b>$label</b>",
    ];
    $element['exceptions']['value'] = array_filter($element['value'], 'is_string', ARRAY_FILTER_USE_KEY);
    $element['exceptions']['value']['#header']['title'] = $this->t('Date');
    // Colspan is a workaround for invisible column #daydelta.
    $element['exceptions']['value']['#header']['operations'] += ['colspan' => 2];
    $element['exceptions']['value']['#attributes'] = ['id' => 'exceptions-container'];
    $element['exceptions']['value'] += $elements;

    $element['exceptions']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add exception'),
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => 'exceptions-container',
        'effect' => 'fade',
      ],
      '#submit' => [
        [static::class, 'addMoreSubmit'],
      ],
    ];

    // Make form_state not cached since we will update it in ajax callback.
    $form_state->setCached(FALSE);

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
   *
   * We need to reproduce the default WidgetBase extractFormValues since:
   * 1. Merge the exceptions values in the main widget values.
   * 2. Exceptions are not directly set in `values` but in `exceptions['values']`.
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    $key_exists = NULL;
    // Extract reference to Exception days values. To be cleared at the end.
    $path = array_merge($form['#parents'], [$field_name, 'exceptions']);
    $exception_values = &NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      if (is_array($exception_values['value'])) {
        // Extract reference to weekday values from $form_state->getValues().
        $path = array_merge($form['#parents'], [$field_name]);
        $values = &NestedArray::getValue($form_state->getValues(), $path);

        // Merge, move, exceptions values to reference to normal values array.
        $values['value'] = array_merge($values['value'], $exception_values['value']);

        // Remove exceptions from NestedArray::getValue($form_state->getValues()).
        $exception_values['value'] = '';
      }
    }

    parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // This is called by parent::extractFormValues().
    if (is_array($values['value'])) {
      $day = '';
      foreach ($values['value'] as &$value) {
        if ($value['day'] == '' || OfficeHoursDateHelper::isExceptionDay($value)) {
          if (!OfficeHoursItem::isValueEmpty($value)) {

            // Copy Exception day number from delta 0 to 'more' slots,
            // in case user changed day number
            // and to avoid removing lines with day but empty hours.
            if (isset($value['day_delta']) && $value['day_delta'] == 0) {
              // Reset value, to be re-used for day_delta 1, 2, ...
              $day = $value['day'];
            }
            $value['day'] = $day;
          }
        }
      }
    }

    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

}
