<?php

namespace Drupal\search_api_location\LocationInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\ConfigurablePluginBase;

/**
 * Defines a base class from which other data type classes may extend.
 */
abstract class LocationInputPluginBase extends ConfigurablePluginBase implements LocationInputInterface {

  /**
   * {@inheritdoc}
   */
  public function hasInput(array $input, array $options) {
    $input['value'] = trim($input['value']);
    if (!$input['value'] || !($options['operator'] || is_numeric($input['distance']['from']))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'radius_type' => 'select',
      'radius_options' => "- -\n5 5 km\n10 10 km\n16.09 10 mi",
      'radius_units' => 'km',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['radius_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of distance input'),
      '#description' => $this->t('Select the type of input element for the distance option.'),
      '#options' => [
        'select' => $this->t('Select'),
        'textfield' => $this->t('Text field'),
      ],
      '#default_value' => $this->configuration['radius_type'],
    ];

    $form['radius_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Distance options'),
      '#description' => $this->t('Add one line per option for “Range” you want to provide. The first part of each line is the distance in kilometres, everything after the first space is the label. "-" as the distance ignores the location for filtering, but will still use it for facets, sorts and distance calculation. Skipping the distance altogether (i.e., starting the line with a space) will provide an option for ignoring the entered location completely.'),
      '#default_value' => $this->configuration['radius_options'],
      '#states' => [
        'visible' => [
          'select[name="options[plugin-' . $this->pluginId . '][radius_type]"]' => ['value' => 'select'],
        ],
      ],
    ];

    $form['radius_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Distance units'),
      '#description' => $this->t('Choose the units for the distance.'),
      '#default_value' => $this->configuration['radius_units'],
      '#options' => array_column(search_api_location_get_units(), 'label', 'id'),
      '#states' => [
        'visible' => [
          'select[name="options[plugin-' . $this->pluginId . '][radius_type]"]' => ['value' => 'textfield'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state, array $options) {
    $plugin_settings = $options['plugin-' . $options['plugin']];
    $build_info = $form_state->getBuildInfo();
    $is_views_ui_form = (!empty($build_info['form_id']) && $build_info['form_id'] == 'views_ui_config_item_form');
    $operator_prefix = '';

    if ($is_views_ui_form) {
      $states_selector = 'input[name="options[operator]"]';
    }
    else {
      $states_selector = 'select[name="' . $options['expose']['operator_id'] . '"]';
    }
    if (!$is_views_ui_form && !($options['exposed'] && $options['expose']['use_operator'])) {
      $operator_prefix = $options['operator_options'][$options['operator']] . '&nbsp;';
    }

    $form['value']['#tree'] = TRUE;

    if ($plugin_settings['radius_type'] == 'select') {
      $distance_options = [];
      $lines = array_filter(array_map('trim', explode("\n", $plugin_settings['radius_options'])));
      foreach ($lines as $line) {
        $pos = strpos($line, ' ');
        $range = substr($line, 0, $pos);
        $distance_options[$range] = trim(substr($line, $pos + 1));
      }

      $form['value']['distance']['from'] = [
        '#type' => 'select',
        '#title' => '&nbsp;',
        '#options' => $distance_options,
        '#default_value' => $options['value']['distance']['from'],
        '#field_prefix' => $operator_prefix,
      ];

      $form['value']['distance']['to'] = [
        '#type' => 'select',
        '#title' => '&nbsp;',
        '#options' => $distance_options,

        '#default_value' => $options['value']['distance']['to'],
        '#field_prefix' => 'and&nbsp;',
        '#states' => [
          'visible' => [
            $states_selector => ['value' => 'between'],
          ],
        ],
      ];
    }
    elseif ($plugin_settings['radius_type'] == 'textfield') {
      $distance_suffix = $plugin_settings['radius_units'];

      $form['value']['distance']['from'] = [
        '#type' => 'textfield',
        '#title' => '&nbsp;',
        '#size' => 5,
        '#default_value' => $options['value']['distance']['from'],
        '#field_prefix' => $operator_prefix,
        '#field_suffix' => $distance_suffix,
      ];

      $form['value']['distance']['to'] = [
        '#title' => '&nbsp;',
        '#type' => 'textfield',
        '#size' => 5,
        '#default_value' => $options['value']['distance']['to'],
        '#field_prefix' => 'and&nbsp;',
        '#field_suffix' => $distance_suffix,
        '#states' => [
          'visible' => [
            $states_selector => ['value' => 'between'],
          ],
        ],
      ];
    }

    if (!$is_views_ui_form && !($options['exposed'] && $options['expose']['use_operator']) && $options['operator'] != 'between') {
      unset($form['value']['distance']['to']);
    }

    $form['value']['value'] = [
      '#type' => 'textfield',
      '#title' => '&nbsp;',
      '#size' => 20,
      '#default_value' => $options['value']['value'],
      '#field_prefix' => 'from&nbsp;',
    ];

    return $form;
  }

}
