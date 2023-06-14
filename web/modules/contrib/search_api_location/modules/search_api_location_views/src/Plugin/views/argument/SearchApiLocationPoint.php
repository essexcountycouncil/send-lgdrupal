<?php

namespace Drupal\search_api_location_views\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\SearchApiHandlerTrait;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Provides a contextual filter for defining a location filter point.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_location_point")
 */
class SearchApiLocationPoint extends ArgumentPluginBase {

  use SearchApiHandlerTrait;
  use SearchApiLocationArgumentTrait;

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['default_radius'] = ['default' => FALSE, 'bool' => TRUE];
    $options['radius'] = ['default' => 10];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['default_radius'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide radius default'),
      '#description' => $this->t('Set a default radius for this contextual filter. Even if set, the radius can be overridden with the corresponding "Radius" contextual filter.'),
      '#default_value' => $this->options['default_radius'],
    ];
    $states['visible'][':input[name="options[default_radius]"]']['checked'] = TRUE;
    $form['radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Radius'),
      '#description' => $this->t('The radius (in km) around the argument point to set the distance filter.'),
      '#required' => TRUE,
      '#default_value' => $this->options['radius'],
      '#states' => $states,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $radius = $form_state->getValue('options')['radius'];
    if (!is_numeric($radius) || $radius <= 0) {
      $form_state->setError($form['radius'], $this->t('You have to enter a numeric radius greater than 0.'));
    }
    parent::validateOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    if ($geofilt = $this->parsePoint($this->argument)) {
      // Add radius from options, if appropriate.
      if ($this->options['default_radius']) {
        $geofilt['radius'] = $this->options['radius'];
      }
      $query = $this->getQuery();
      $location_options = (array) $query->getOption('search_api_location');
      $this->addFieldOptions($location_options, $geofilt, $this->realField);
      $query->setOption('search_api_location', $location_options);
    }
  }

  /**
   * Parses a point into a latitude/longitude pair.
   *
   * @param string $argument
   *   A point specification in any format.
   *
   * @return float[]|false
   *   An associative array with "lat" and "lon" representing a point. Or FALSE
   *   if the format was not recognized.
   */
  protected function parsePoint($argument) {
    $point = [];

    if (class_exists(\geoPHP::class)) {
      // Try to use geoPHP to read type.
      try {
        $format = \geoPHP::detectFormat($argument);
        if ($format) {
          $args = explode(':', $format);
          array_unshift($args, $argument);
          $location = call_user_func_array(['geoPHP', 'load'], $args);
          $point['lat'] = $location->y();
          $point['lon'] = $location->x();
        }
      }
      catch (\Exception $e) {
        // GeoPHP couldn't handle type. Treat as invalid/no argument, silently.
      }
    }

    if (empty($point)) {
      // Try Solr LatLonType.
      if (preg_match("/^([+-]?[0-9]+(?:\\.[0-9]+)?),([+-]?[0-9]+(?:\\.[0-9]+)?)$/", $argument, $match)) {
        $point['lat'] = $match[1];
        $point['lon'] = $match[2];
      }
    }

    return empty($point) ? FALSE : $point;
  }

}
