<?php

namespace Drupal\search_api_best_bets\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api_best_bets\Plugin\Field\FieldType\SearchApiBestBetsFieldItem;

/**
 * Advanced widget for search_api_best_bets field.
 *
 * @FieldWidget(
 *   id = "search_api_best_bets_widget",
 *   label = @Translation("Search API Best Bets form"),
 *   field_types = {
 *     "search_api_best_bets"
 *   },
 *   multiple_values = true
 * )
 */
class SearchApiBestBetsFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'elevate_label' => new TranslatableMarkup('Elevate query'),
      'elevate_placeholder' => new TranslatableMarkup('Write search queries....'),
      'elevate_description' => new TranslatableMarkup('Specify queries that will elevate this entity to the top of the result. Separate multiple by comma.'),
      'exclude_label' => new TranslatableMarkup('Exclude query'),
      'exclude_placeholder' => new TranslatableMarkup('Write search queries....'),
      'exclude_description' => new TranslatableMarkup('Specify queries that will exclude this entity from the search result. Separate multiple by comma.'),
      'disable_exclude' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['elevate_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for elevate query field.'),
      '#description' => $this->t('Text that will be used as label for the elevate query field.'),
      '#default_value' => $this->getSetting('elevate_label'),
    ];

    $elements['elevate_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for elevate query field'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#default_value' => $this->getSetting('elevate_placeholder'),
    ];

    $elements['elevate_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description for elevate query field.'),
      '#description' => $this->t('Help text that will be shown below the field'),
      '#default_value' => $this->getSetting('elevate_description'),
    ];

    $elements['disable_exclude'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable "exclude"'),
      '#description' => $this->t('Disable the "exclude" feature if not supported by the search backend.'),
      '#default_value' => $this->getSetting('disable_exclude'),
    ];

    $elements['exclude_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for exclude query field.'),
      '#description' => $this->t('Text that will be used as label for the exclude query field.'),
      '#default_value' => $this->getSetting('exclude_label'),
    ];

    $elements['exclude_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for exclude query field'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#default_value' => $this->getSetting('exclude_placeholder'),
    ];

    $elements['exclude_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description for exclude query field.'),
      '#description' => $this->t('Help text that will be shown below the field'),
      '#default_value' => $this->getSetting('exclude_description'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    // Elevate label.
    $label = $this->getSetting('elevate_label');
    $summary[] = $this->t('Elevate query label: @label', ['@label' => $label]);

    // Elevate placeholder.
    $placeholder = $this->getSetting('elevate_placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Elevate query placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }

    // Elevate description.
    $description = $this->getSetting('elevate_description');
    if (!empty($description)) {
      $summary[] = $this->t('Elevate query description: @description', ['@description' => $description]);
    }

    // Disable exclude.
    $label = $this->getSetting('disable_exclude') ? $this->t('No') : $this->t('Yes');
    $summary[] = $this->t('Show "exclude" feature: @show_exclude', ['@show_exclude' => $label]);

    if (!$this->getSetting('disable_exclude')) {
      // Elevate label.
      $label = $this->getSetting('exclude_label');
      $summary[] = $this->t('Exclude query label: @label', ['@label' => $label]);

      // Elevate placeholder.
      $placeholder = $this->getSetting('exclude_placeholder');
      if (!empty($placeholder)) {
        $summary[] = $this->t('Exclude query placeholder: @placeholder', ['@placeholder' => $placeholder]);
      }

      // Exclude description.
      $description = $this->getSetting('exclude_description');
      if (!empty($description)) {
        $summary[] = $this->t('Exclude query description: @description', ['@description' => $description]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_values = $this->massageDefaultArrayValues($items);

    $element += [
      '#type' => 'details',
      '#group' => 'advanced',
    ];
    $element['elevate'] = [
      '#type' => 'textarea',
      '#title' => $this->getSetting('elevate_label'),
      '#placeholder' => $this->getSetting('elevate_placeholder'),
      '#description' => $this->t('Write the elevate queries in the field - separate multiple by comma.'),
      '#default_value' => $default_values['elevate'] ?? NULL,
      '#maxlength' => 360,
      '#required' => $element['#required'],
    ];

    if (!$this->getSetting('disable_exclude')) {
      $element['exclude'] = [
        '#type' => 'textarea',
        '#title' => $this->getSetting('exclude_label'),
        '#placeholder' => $this->getSetting('exclude_placeholder'),
        '#description' => $this->getSetting('exclude_description'),
        '#default_value' => $default_values['exclude'] ?? NULL,
        '#maxlength' => 360,
        '#required' => $element['#required'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $fixed_values = [];

    // Massage the comma separated values to the array style that is expected
    // by the field type.
    if (!empty($values['elevate'])) {
      $fixed_values = array_merge($fixed_values, $this->massageSeparatedValues($values['elevate']));
    }
    if (!empty($values['exclude'])) {
      $fixed_values = array_merge($fixed_values, $this->massageSeparatedValues($values['exclude'], TRUE));
    }

    return $fixed_values;
  }

  /**
   * Massage a comma separated string to the array style expected by field type.
   *
   * @param string $values
   *   The comma separated string of values.
   * @param bool $exclude
   *   Elevate or Exclude query.
   *
   * @return array
   *   Array with the massaged values.
   */
  private function massageSeparatedValues($values, $exclude = FALSE) {
    $source_values = explode(',', $values);
    $fixed_values = [];

    foreach ($source_values as $value) {
      if (trim($value)) {
        $fixed_values[] = [
          'query_text' => mb_strtolower(trim($value)),
          'exclude' => (int) $exclude,
        ];
      }
    }
    return $fixed_values;
  }

  /**
   * Massage the default array values into comma separated strings.
   *
   * @param iterable $items
   *   Array of field list item objects.
   *
   * @return array
   *   Array with comma separated strings.
   */
  private function massageDefaultArrayValues(iterable $items) {
    $fixed_values = [
      'elevate' => [],
      'exclude' => [],
    ];

    foreach ($items as $item) {
      if ($item instanceof SearchApiBestBetsFieldItem && $value = $item->getValue()) {
        $type = $value['exclude'] ? 'exclude' : 'elevate';
        $fixed_values[$type][] = $value['query_text'];
      }
    }

    foreach ($fixed_values as $key => $values) {
      $fixed_values[$key] = implode(', ', $values);
    }

    return $fixed_values;
  }

}
