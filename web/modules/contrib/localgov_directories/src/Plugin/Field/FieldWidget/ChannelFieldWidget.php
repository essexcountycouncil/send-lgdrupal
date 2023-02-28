<?php

namespace Drupal\localgov_directories\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Plugin to display directory channels.
 *
 * @FieldWidget(
 *   id = "localgov_directories_channel_selector",
 *   module = "localgov_directories",
 *   label = @Translation("Directory channels"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ChannelFieldWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $primary_options = $secondary_options = $this->getOptions($items->getEntity());
    $secondary_selected = $this->getSelectedOptions($items);
    $primary_selected = array_shift($secondary_selected);

    $element += [
      '#type' => 'fieldset',
    ];

    if (empty($primary_options)) {
      $element['#description'] = $this->t('The directory channels this content should be found in. Will change the available facets.');
    }

    $ajax = [
      'callback' => [
        '\Drupal\localgov_directories\Plugin\Field\FieldWidget\ChannelFacetInteractions',
        'updateFields',
      ],
      'disable-refocus' => FALSE,
      'event' => 'change',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Loading facets...'),
      ],
    ];

    $element['primary'] = [
      '#title' => $this->t('Primary'),
      '#type' => 'radios',
      '#default_value' => $primary_selected,
      '#options' => $primary_options,
      '#description' => $this->t('The primary directory this appears in. Path, breadcrumb, will be set for this directory'),
      '#ajax' => $ajax,
    ];

    $element['secondary'] = [
      '#title' => $this->t('Others'),
      '#type' => 'checkboxes',
      '#default_value' => $secondary_selected,
      '#options' => $secondary_options,
      '#description' => $this->t('Other directories this will appear in.'),
      '#ajax' => $ajax,
    ];
    foreach ($secondary_options as $key => $value) {
      $element['secondary'][$key]['#states']['invisible'] = [':input[name=localgov_directory_channels\[primary\]]' => ['value' => $key]];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // Flatten the array again.
    if ($form_state->get('default_value_widget')) {
      $values = $form_state->getValue('default_value_input')[$element['#field_name']];
    }
    else {
      $values = $form_state->getValue($element['#field_name']);
    }
    if ($values) {
      $element['#value'] = array_filter(
        [$values['primary'] => $values['primary']] + $values['secondary']
      );
    }

    parent::validateElement($element, $form_state);
  }

  /**
   * AJAX callback to rebuild form fields dependent on selected channels.
   *
   * Presently hard codes the one field - by name.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Form array for field.
   */
  public static function updateFacets(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form['localgov_directory_facets_select'];
  }

}
