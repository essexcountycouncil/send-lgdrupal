<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours_status",
 *   label = @Translation("Status"),
 *   field_types = {
 *     "office_hours",
 *     "office_hours_status",
 *   }
 * )
 */
class OfficeHoursFormatterStatus extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element_save = $element['current_status'];

    $element = [];
    $element['current_status'] = $element_save;
    $element['current_status']['#type'] = 'fieldset';
    $element['current_status']['position']['#type'] = 'hidden';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t("Display only 'Closed'/'Opened' text.");
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Alter the default settings, to calculate the cache correctly.
    // The status formatter has no UI for this setting.
    $this->setSetting('show_closed', 'next');
    $settings = $this->getSettings();
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $field_definition = $items->getFieldDefinition();
    $elements += [
      '#theme' => 'office_hours_status',
      '#parent' => $field_definition,
      '#is_open' => $items->isOpen(),
      '#open_text' => (string) $this->t($settings['current_status']['open_text']),
      '#closed_text' => (string) $this->t($settings['current_status']['closed_text']),
      '#position' => $this->settings['current_status']['position'],
    ];

    // Add a ['#cache']['max-age'] attribute to $elements.
    // Note: This invalidates a previous Cache in Status Formatter.
    $this->addCacheMaxAge($items, $elements);

    return $elements;
  }

}
