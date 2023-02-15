<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours_table_details",
 *   label = @Translation("Table Select list"),
 *   field_types = {
 *     "office_hours",
 *   }
 * )
 */
class OfficeHoursFormatterTableSelectList extends OfficeHoursFormatterTable {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // @todo Make sure the correct line is overridden.
    $summary[2] = $this->t('Display Office hours in a openable Select list.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
    $elements = parent::viewElements($items, $langcode);

    // Get the formatters.
    $status_formatter = NULL;
    $hours_formatter = NULL;
    foreach ($elements as $key => $element) {
      if (isset($element['#theme'])) {
        switch ($element['#theme']) {
          case 'office_hours':
          case 'office_hours_table':
            $hours_key = $key;
            break;

          case 'office_hours_status':
            $status_formatter[0] = $element;
            // Remove, since moved to Details Title.
            unset($elements[$key]);
            break;
        }
      }
    }

    // Convert formatter to Select List ('details' render element).
    if (isset($hours_key)) {
      // Get/create the status formatter, holding the 'current'/'next' time slot.
      $position = $this->settings['current_status']['position'];
      $this->settings['current_status']['position'] = 'before';
      $status_formatter = $status_formatter ?? $this->addStatusFormatter($items, $langcode, []);
      $status_formatter = reset($status_formatter);

      // Add a ['#cache']['max-age'] attribute to $elements.
      // Note: This invalidates a previous Cache in Status Formatter.
      $this->addCacheMaxAge($items, $elements);

      // Reset the attribute.
      $this->settings['current_status']['position'] = $position;

      // Get the 'current' time slot, if applicable.
      $current_slot = NULL;
      $is_open = $status_formatter['#is_open'];
      $hours_formatter = $elements[$hours_key];
      if ($is_open) {
        // Get currently open slot.
        // @todo Add to OfficeHoursItemList? But this is already formatted.
        // $current_slot = $items->getCurrentSlot();.
        foreach ($hours_formatter['#office_hours'] as $item) {
          if ($item['current'] == TRUE) {
            $current_slot = $item['formatted_slots'];
          }
        }
      }

      // Use the 'open_text' and 'current' slot to set the title.
      // Note: The theming class for 'current' is set in the parent formatter.
      $title = $is_open
        ? $this->t($status_formatter['#open_text']) . ' ' . $current_slot ?? ''
        : $this->t($status_formatter['#closed_text']);

      // Add the extra render element data.
      $elements[$hours_key] += [
        '#type' => 'details',
        '#title' => $title,
      ];
    }

    return $elements;
  }

}
