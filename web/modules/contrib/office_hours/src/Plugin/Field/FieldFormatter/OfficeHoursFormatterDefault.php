<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "office_hours",
 *   }
 * )
 */
class OfficeHoursFormatterDefault extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if (get_class($this) == __CLASS__) {
      // Avoids message when class overridden. Parent repeats it when needed.
      $summary[] = '(When using multiple slots per day, better use the table formatter.)';
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // If no data is filled for this entity, do not show the formatter.
    if ($items->isEmpty()) {
      return $elements;
    }

    $settings = $this->getSettings();
    $third_party_settings = $this->getThirdPartySettings();
    $field_definition = $items->getFieldDefinition();
    // N.B. 'Show current day' may return nothing in getRows(),
    // while other days are filled.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $office_hours = $items->getRows($settings, $this->getFieldSettings(), $third_party_settings);

    $elements[] = [
      '#theme' => 'office_hours',
      '#parent' => $field_definition,
      // Pass filtered office_hours structures to twig theming.
      '#office_hours' => $office_hours,
      // Pass (unfiltered) office_hours items to twig theming.
      '#office_hours_field' => $items,
      '#is_open' => $items->isOpen(),
      '#item_separator' => $settings['separator']['days'],
      '#slot_separator' => $settings['separator']['more_hours'],
      '#attributes' => [
        'class' => ['office-hours'],
      ],
      // '#empty' => $this->t('This location has no opening hours.'),
      '#attached' => [
        'library' => [
          'office_hours/office_hours_formatter',
        ],
      ],
    ];

    $elements = $this->addSchemaFormatter($items, $langcode, $elements);
    $elements = $this->addStatusFormatter($items, $langcode, $elements);

    // Add a ['#cache']['max-age'] attribute to $elements.
    // Note: This invalidates a previous Cache in Status Formatter.
    $this->addCacheMaxAge($items, $elements);

    return $elements;
  }

}
