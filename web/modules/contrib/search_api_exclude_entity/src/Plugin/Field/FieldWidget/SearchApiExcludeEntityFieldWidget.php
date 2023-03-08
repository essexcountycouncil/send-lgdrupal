<?php

namespace Drupal\search_api_exclude_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Advanced widget for search_api_exclude_entity field.
 *
 * @FieldWidget(
 *   id = "search_api_exclude_entity_widget",
 *   label = @Translation("Search API Exclude form"),
 *   field_types = {
 *     "search_api_exclude_entity"
 *   }
 * )
 */
class SearchApiExcludeEntityFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'field_label' => new TranslatableMarkup('Yes, exclude this entity from the search indexes.'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'details',
      '#group' => 'advanced',
    ];

    $element['value'] = [
      '#type' => 'checkbox',
      '#default_value' => !empty($items[0]->value),
      '#title' => $this->getSetting('field_label'),
      '#required' => !empty($element['#required']) ? $element['#required'] : FALSE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Checkbox label'),
      '#description' => $this->t('Text used as label next to the field checkbox.'),
      '#default_value' => $this->getSetting('field_label'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $field_label = $this->getSetting('field_label');
    if (!empty($field_label)) {
      $summary[] = $this->t('Checkbox label: @field_label', ['@field_label' => $field_label]);
    }

    return $summary;
  }

}
