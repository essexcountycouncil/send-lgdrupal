<?php

/**
 * @file
 * Implements third_party_settings hooks provided by the Field UI module.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\office_hours\OfficeHoursDateHelper;

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
function office_hours_exceptions_preprocess_field(array &$variables) {
  if (substr($variables['field_type'], 0, 12) !== 'office_hours') {
    return;
  }

  $entity = $variables['element']['#object'];
  $view_mode = $variables['element']['#view_mode'];
  $field_name = $variables['element']['#field_name'];

  // Get the field formatter settings.
  $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
  $settings = $entity_display->getComponent($field_name)['settings'];

  /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
  $items = &$variables['element']['#items'];
  $langcode = $variables['element']['#language'];
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
  $label = $settings['exceptions']['title'] ?? '';
  foreach ($elements as $key => &$element) {
    if (is_numeric($key)) {
      // If there is an exception, add an extra row to label the exceptions.
      // Note: may be changed in template_preprocess_office_hours_table().
      $exception_header = [];
      switch ($element['content']['#theme']) {

        case 'office_hours_table':
          $formatter_rows = &$element['content']['#table']['#rows'];
          if ($label && $formatter_rows) {
            // Set the title for the exceptions.
            $exception_header['data']['label']['data']['#markup'] = $label;
            $exception_header['class'] = [
              'office-hours__exceptions-label',
            ];
            $exception_header['id'] = ['office-hours-exception__title'];
          }
          break;

        case 'office_hours':
          $formatter_rows = &$element['content']['#office_hours'];
          if ($label && $formatter_rows) {
            // Set the title for the exceptions.
            $exception_header['title'] = $label;
            $exception_header['label'] = $label;
            // Set everything else to NULL.
            $exception_header['slots'] = NULL;
            $exception_header['formatted_slots'] = NULL;
            $exception_header['index'] = NULL;
            $exception_header['comments'] = NULL;
          }
          break;

        case 'office_hours_schema':
          // @todo Test/Enhance this formatter.
        case 'office_hours_status':
          // @todo Test/Enhance this formatter.
        default:
          break;
      }
      if ($exception_header) {
        // Keys 0-7 are for sorted weekdays, so adding our 8.
        $formatter_rows[8] = $exception_header;
        // Sort, to ensure that the header is before the exceptions.
        $formatter_rows = OfficeHoursDateHelper::weekDaysOrdered($formatter_rows, $settings['office_hours_first_day']);
      }
    }
  }

  return $elements;
}
