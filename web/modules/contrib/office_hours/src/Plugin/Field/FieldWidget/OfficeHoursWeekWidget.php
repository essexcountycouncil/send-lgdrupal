<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

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
 */
class OfficeHoursWeekWidget extends OfficeHoursWidgetBase {

  /**
   * {@inheritdoc}
   *
   * Note: This is never called, since Annotation: multiple_values = "FALSE".
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
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
  public static function defaultSettings() {
    return [
      'collapsed' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsed'),
      '#default_value' => $this->getSetting('collapsed'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Collapsed: @collapsed', ['@collapsed' => $this->getSetting('collapsed') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

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

    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $items->filterEmptyItems();

    // Use seasonal, or normal Weekdays (empty season).
    $season = $this->getSeason();
    // Create an indexed two level array of time slots:
    // - First level are day numbers.
    // - Second level contains items arranged by $day_delta.
    $indexed_items = array_fill_keys(range(0, 6), []);
    foreach ($items as $item) {
      // Only add relevant Weekdays/Season days.
      $value = $item->getValue();
      $day = $value['day'];
      if ($season->isInSeason($day)) {
        $day = $season->getWeekday($day);
        $value['day'] = $day;
        $item->setValue($value);
        $indexed_items[$day][] = $item;
      }
    }

    // Build elements, sorted by first_day_of_week.
    $elements = [];
    $days = OfficeHoursDateHelper::weekDaysOrdered(range(0, 6));
    $cardinality = $this->getFieldSetting('cardinality_per_day');
    foreach ($days as $day) {
      $day_index = $day;

      for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
        $item = $indexed_items[$day][$day_delta] ?? $items->appendItem(['day' => $day]);
        $default_value = $item->getValue();
        $elements[] = [
          '#type' => 'office_hours_slot',
          '#default_value' => $default_value,
          '#day_index' => $day_index,
          '#day_delta' => $day_delta,
          // Add field settings, for usage in each Element.
          '#field_settings' => $this->getFieldSettings(),
          '#date_element_type' => $this->getSetting('date_element_type'),
        ];
      }
    }

    // Build multi element widget. Copy the description, etc. into the table.
    // Use the more complex 'data' construct for obsolete reasons.
    $header = OfficeHoursItem::getPropertyLabels('data', $this->getFieldSettings());
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
    $element['#open'] = !$this->getSetting('collapsed');

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
