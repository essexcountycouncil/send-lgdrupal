<?php

/**
 * @file
 * Implements third_party_settings hooks provided by the Field UI module.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/* @see File core/modules/field_ui/field_ui.api.php
 *
 * hook_field_formatter_settings_summary_alter
 * 	 Alters the field formatter settings summary.
 * hook_field_formatter_third_party_settings_form
 * 	 Allow modules to add field formatter settings, provided by other modules.
 * hook_field_widget_settings_summary_alter
 *   Alters the field widget settings summary.
 * hook_field_widget_third_party_settings_form
 *   Allow modules to add field widget settings, provided by other modules.
 */

/**
 * Implements hook_preprocess_field().
 */
function office_hours_exceptions_preprocess_field(array &$variables, $hook) {
  if (substr($variables['field_type'], 0, 12) !== 'office_hours') {
    return;
  }

  $element = $variables['element'];
  $entity = $element['#object'];
  $view_mode = $element['#third_party_settings']['layout_builder']['view_mode'] ?? $element['#view_mode'];
  $field_name = $element['#field_name'];

  // Get the field formatter settings.
  $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
  $settings = $entity_display->getComponent($field_name)['settings'] ?? [];

  /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
  $items = &$variables['element']['#items'];
  $elements = &$variables['items'];

  if ($items->isEmpty()) {
    return $elements;
  }
  // Check if an exception day exists in the table.
  if (!$items->hasExceptionDays()) {
    return $elements;
  }

  // If field settings for this view_mode are not set, yet.
  if (!isset($settings['exceptions']['title'])) {
    \Drupal::messenger()->addMessage(t("Please review the display settings
      for field %field_name in view mode %view_mode, and save them again.", [
        '%field_name' => $field_name,
        '%view_mode' => $view_mode,
      ]
    ));
    return $elements;
  }

  // Loop over formatters: $elements contains table/status/schema formatter.
  foreach ($elements as $key => &$element) {
    if (is_numeric($key)) {
      // If there is an exception, add an extra row to label the exceptions.
      // Note: may be changed in template_preprocess_office_hours_table().
      $exception_header = [];
      switch ($element['content']['#theme']) {

        case 'office_hours_table':
          $formatter_rows = &$element['content']['#table']['#rows'];
          if ($formatter_rows[OfficeHoursItem::EXCEPTION_DAY] ?? NULL) {
            $exception_header = $formatter_rows[OfficeHoursItem::EXCEPTION_DAY];
            $exception_header['data']['label']['class'] = [
              'office-hours__exceptions-label',
            ];
            $exception_header['id'] = ['office-hours-exception__title'];
            // Set everything but title to NULL.
            $exception_header['data']['slots'] = NULL;
            $exception_header['data']['comments'] = NULL;
          }
          break;

        case 'office_hours':
          $formatter_rows = &$element['content']['#office_hours'];
          if ($formatter_rows[OfficeHoursItem::EXCEPTION_DAY] ?? NULL) {
            $exception_header = $formatter_rows[OfficeHoursItem::EXCEPTION_DAY];
            // Set everything but title to NULL.
            $exception_header['slots'] = NULL;
            $exception_header['formatted_slots'] = NULL;
            $exception_header['index'] = NULL;
            $exception_header['comments'] = NULL;
          }
          break;

        case 'office_hours_schema':
          // @todo Test/Enhance this formatter for Exception days.
        case 'office_hours_status':
          // @todo Test/Enhance this formatter for Exception days.
        default:
          break;
      }
      if ($exception_header) {
        // Complete the exception header.
        $formatter_rows[OfficeHoursItem::EXCEPTION_DAY] = $exception_header;
      }
    }
  }

  return $elements;
}
