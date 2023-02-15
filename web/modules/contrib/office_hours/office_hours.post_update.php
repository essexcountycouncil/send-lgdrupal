<?php

/**
 * @file
 * Post update functions for Office Hours.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Updates Office Hours 'default value' schema in field config in 8.x-1.3.
 */
function office_hours_post_update_implement_office_hours_default_value_config_schema(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'field_config', function (FieldConfigInterface $fieldConfig) {
      if ($fieldConfig->getFieldStorageDefinition()->getType() !== 'office_hours') {
        return FALSE;
      }

      $default_values = $fieldConfig->getDefaultValueLiteral();
      foreach ($default_values as $key => $default_value_row) {
        $value = [
          'day' => (int) $default_value_row['day'],
          'starthours' => (int) $default_value_row['starthours'],
          'endhours' => (int) $default_value_row['endhours'],
          'comment' => (string) $default_value_row['comment'],
        ];
        $default_values[$key] = $value;
      }
      $fieldConfig->setDefaultValue($default_values);
      return TRUE;
    });
}

/**
 * Updates Office Hours 'formatter.settings' schema in entity view display.
 */
function office_hours_post_update_implement_office_hours_entity_view_display_schema(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $entityViewDisplay) {
      $updated = FALSE;
      foreach ($entityViewDisplay->getComponents() as $key => $component) {
        if ($component['type'] === 'office_hours_table') {
          $component['settings']['compress'] = (bool) $component['settings']['compress'];
          $component['settings']['grouped'] = (bool) $component['settings']['grouped'];
          $component['settings']['schema']['enabled'] = (bool) $component['settings']['schema']['enabled'];
          $entityViewDisplay->setComponent($key, $component);
          $updated = TRUE;
        }
      }
      return $updated;
    });
}
