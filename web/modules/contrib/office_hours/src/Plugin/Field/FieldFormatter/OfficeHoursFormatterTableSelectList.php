<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

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

    // Activate the current_status position. It might be off in Field UI.
    // This is needed for correct addCacheMaxAge().
    $this->settings['current_status']['position'] = 'before';

    $elements = parent::viewElements($items, $langcode);

    // If no data is filled for this entity, do not show the formatter.
    if ($items->isEmpty()) {
      return $elements;
    }

    // Process the given formatters.
    $hours_formatter = NULL;
    foreach ($elements as $key => $element) {
      switch ($element['#theme'] ?? '') {
        case 'office_hours':
        case 'office_hours_table':
          // Fetch the Office Hours formatter.
          $hours_formatter = &$elements[$key];
          break;

        case 'office_hours_status':
          // Remove the Status Formatter. Moved/Re-determined to Details Title.
          unset($elements[$key]);
          break;
      }
    }

    if ($hours_formatter) {
      // Convert formatter to Select List ('details' render element),
      // adding the extra render element data.
      // Note: The theming class for 'current' is set in the parent formatter.
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
      $hours_formatter += [
        '#type' => 'details',
        '#title' => $this->getStatusTitle($items, $hours_formatter),
      ];
    }

    return $elements;
  }

  /**
   * Generates the title for the 'details' formatter.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   An Office Hours ItemList object.
   * @param array $hours_formatter
   *   An Office Hours formatter array.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Title of the element.
   */
  private function getStatusTitle(OfficeHoursItemListInterface $items, array $hours_formatter) {
    $settings = $this->getSettings();

    // Use the 'open_text' and 'current' slot to set the title.
    $current_item = $items->getCurrent();
    if ($current_item) {
      // Get details from currently open slot.
      $item = $hours_formatter['#office_hours'][$current_item->getValue()['day']];
      $formatted_slots = $item['formatted_slots'];
      $label = $item['label'];
      $status_text = $this->t($settings['current_status']['open_text']);
      $title = $status_text . ' ' . $label . ' ' . $formatted_slots;
    }
    else {
      $status_text = $this->t($settings['current_status']['closed_text']);
      $title = $status_text;
    }

    return $title;
  }

}
