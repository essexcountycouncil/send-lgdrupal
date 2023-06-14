<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours_table",
 *   label = @Translation("Table"),
 *   field_types = {
 *     "office_hours",
 *   }
 * )
 */
class OfficeHoursFormatterTable extends OfficeHoursFormatterDefault {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Display Office hours in a table.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
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
      }
    }

    if (!$hours_formatter) {
      return $elements;
    }

    // N.B. 'Show current day' may return nothing in getRows(),
    // while other days are filled.
    $office_hours = $hours_formatter['#office_hours'];

    $settings = $this->getSettings();
    $field_definition = $items->getFieldDefinition();
    // Add a label/header/title for accessibility (a11y) screen readers.
    // Superfluous comments are removed. @see #3110755 for examples.
    $isLabelEnabled = $settings['day_format'] != 'none';
    $isTimeSlotEnabled = TRUE;
    $isCommentEnabled = $this->getFieldSetting('comment');

    // Build the Table part.
    $table_rows = [];
    foreach ($office_hours as $delta => $item) {
      $table_rows[$delta] = [
        'data' => [],
        'no_striping' => TRUE,
        'class' => ['office-hours__item'],
      ];
      if ($item['current'] == TRUE) {
        $table_rows[$delta]['class'][] = 'office-hours__item-current';
      }

      if ($isLabelEnabled) {
        $table_rows[$delta]['data']['label'] = [
          'data' => ['#markup' => $item['label']],
          'class' => ['office-hours__item-label'],
          'header' => !$isCommentEnabled, // Switch 'Day' between <th> and <tr>.
        ];
      }
      if ($isTimeSlotEnabled) {
        $table_rows[$delta]['data']['slots'] = [
          'data' => ['#markup' => $item['formatted_slots']],
          'class' => ['office-hours__item-slots'],
        ];
      }
      if ($isCommentEnabled) {
        $table_rows[$delta]['data']['comments'] = [
          'data' => ['#markup' => $item['comments']],
          'class' => ['office-hours__item-comments'],
        ];
      }
    }

    $table = [
      '#theme' => 'table',
      '#parent' => $field_definition,
      '#attributes' => [
        'class' => ['office-hours__table'],
      ],
      // '#empty' => $this->t('This location has no opening hours.'),
      '#rows' => $table_rows,
      '#attached' => [
        'library' => [
          'office_hours/office_hours_formatter',
        ],
      ],
    ];

    if ($isCommentEnabled) {
      $labels = OfficeHoursItem::getPropertyLabels('data', $this->getFieldSettings() + ['slots' => TRUE]);

      if ($isLabelEnabled) {
        $table['#header']['label'] = [
          'data' => $labels['day']['data'],
          'class' => 'visually-hidden',
        ];
      }
      $table['#header']['slots'] = [
        'data' => $labels['slots']['data'],
        'class' => 'visually-hidden',
      ];
      $table['#header']['comments'] = [
        'data' => $labels['comment']['data'],
        'class' => 'visually-hidden',
      ];
    }

    // Overwrite parent.
    $hours_formatter['#theme'] = 'office_hours_table';
    $hours_formatter['#table'] = $table;

    return $elements;
  }

}
