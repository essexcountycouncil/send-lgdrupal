<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Base class for the 'office_hours_*' widgets.
 */
abstract class OfficeHoursWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get field settings, to make it accessible for each element in other functions.
    $settings = $this->getFieldSettings();

    $element['#field_settings'] = $settings;
    $element['value'] = [
      '#field_settings' => $settings,
      '#attached' => [
        'library' => [
          'office_hours/office_hours_widget',
        ],
      ],
    ];

    return $element;
  }

  // public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
  //   parent::extractFormValues($items, $form, $form_state);
  // }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // N.B. The $values are already reformatted in the subWidgets.
    foreach ($values as $key => &$value) {
      // Note: below could better be done in OfficeHoursItemList::filter().
      // However, then we have below error "value '' is not allowed".
      // Or "This value should be of the correct primitive type".
      if (OfficeHoursItem::isValueEmpty($value)) {
        unset($values[$key]);
        continue;
      }

      // Normalize values.
      // Value of hours can be NULL, '', '0000', or a proper time.
      OfficeHoursItem::formatValue($value);

      // Allow Empty time field with comment (#2070145).
      // In principle, this is prohibited by the database: value '' is not
      // allowed. The format is int(11).
      // Would changing the format to 'string' help?
      // Perhaps, but using '-1' works, too.
      $value['starthours'] = $value['starthours'] ?? OfficeHoursDateHelper::EMPTY_HOURS;
      $value['endhours'] = $value['endhours'] ?? OfficeHoursDateHelper::EMPTY_HOURS;
    }

    return $values;
  }

}
