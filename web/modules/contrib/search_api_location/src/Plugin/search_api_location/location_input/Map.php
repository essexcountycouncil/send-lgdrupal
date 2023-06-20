<?php

namespace Drupal\search_api_location\Plugin\search_api_location\location_input;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_location\LocationInput\LocationInputPluginBase;

/**
 * Represents the Raw Location Input.
 *
 * @LocationInput(
 *   id = "geocode_map",
 *   label = @Translation("Map"),
 *   description = @Translation("Let user choose a location on a map."),
 * )
 */
class Map extends LocationInputPluginBase {

  /**
   * {@inheritdoc}
   */
  public function hasInput(array $input, array $options) {
    return ($input['lat'] && $input['lng']);
  }

  /**
   * {@inheritdoc}
   */
  public function getParsedInput(array $input) {
    if (!isset($input['lat']) || !isset($input['lng'])) {
      throw new \InvalidArgumentException('Input doesn\'t contain a location value.');
    }
    return $input['lat'] . ',' . $input['lng'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'radius_border_color' => '',
      'radius_border_weight' => '',
      'radius_background_color' => '',
      'radius_background_transparency' => '',
      'marker_image' => '',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['radius_border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border color'),
      '#description' => $this->t('The hexadecimal value of the radius border color.'),
      '#default_value' => $this->configuration['radius_border_color'],
      '#size' => 7,
    ];

    $form['radius_border_weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border weight'),
      '#description' => $this->t('The radius border weight in pixels.'),
      '#default_value' => $this->configuration['radius_border_weight'],
      '#size' => 3,
    ];

    $form['radius_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fill color'),
      '#description' => $this->t('The hexadecimal value of the fill color.'),
      '#default_value' => $this->configuration['radius_background_color'],
      '#size' => 7,
    ];

    $form['radius_background_transparency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fill transparency'),
      '#description' => $this->t('The opacity of the fill color (a value between 0.0 and 1.0)'),
      '#default_value' => $this->configuration['radius_background_transparency'],
      '#size' => 3,
    ];

    $form['marker_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Marker image'),
      '#description' => $this->t('The path to the marker image.'),
      '#default_value' => $this->configuration['marker_image'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state, array $options) {

    $form['value']['#tree'] = TRUE;

    // Get the default values for existing field.
    $lat_default_value = (float) $form_state->getUserInput()[$options['expose']['identifier']]['lat'] ?: "51.037932";
    $lng_default_value = (float) $form_state->getUserInput()[$options['expose']['identifier']]['lng'] ?: "3.712105";
    $radius_default_value = (float) $form_state->getUserInput()[$options['expose']['identifier']]['distance']['from'] ?: "1000";

    $id = $options['id'];

    // Hidden lat,lng input fields.
    $form['value']['lat'] = [
      '#type' => 'hidden',
      '#default_value' => $lat_default_value,
      '#attributes' => ['id' => ["sal-{$id}-lat"]],
    ];
    $form['value']['lng'] = [
      '#type' => 'hidden',
      '#default_value' => $lng_default_value,
      '#attributes' => ['id' => ["sal-{$id}-lng"]],
    ];

    $form['value']['distance']['from'] = [
      '#type' => 'hidden',
      '#default_value' => $radius_default_value,
      '#attributes' => ['id' => ["sal-{$id}-radius"]],
    ];

    // Add the map container.
    $form['value']['map'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => "sal-{$id}-map",
        'class' => "sal-map",
      ],
      '#attached' => [
        'library' => ['search_api_location/location-picker'],
        'drupalSettings' => [
          'search_api_location' => [
            $id => [
              'id' => $id,
              'lat' => (float) $lat_default_value,
              'lng' => (float) $lng_default_value,
              'radius' => $radius_default_value,
              'radius_border_color' => $options['plugin-geocode_map']['radius_border_color'],
              'radius_border_weight' => $options['plugin-geocode_map']['radius_border_weight'],
              'radius_background_color' => $options['plugin-geocode_map']['radius_background_color'],
              'radius_background_transparency' => $options['plugin-geocode_map']['radius_background_transparency'],
              'marker_image' => $options['plugin-geocode_map']['marker_image'],
            ],
          ],
        ],
      ],
    ];

    $form['value']['slider'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => "sal-{$id}-slider",
        'class' => "sal-slider",
      ],
    ];

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement validateConfigurationForm() method.
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitConfigurationForm() method.
  }

}
