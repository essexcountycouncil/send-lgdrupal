<?php

namespace Drupal\search_api_location_views\Plugin\views\argument;

use Drupal\search_api\Plugin\views\SearchApiHandlerTrait;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Provides a contextual filter for defining a location filter radius.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_location_radius")
 */
class SearchApiLocationRadius extends ArgumentPluginBase {

  use SearchApiHandlerTrait;
  use SearchApiLocationArgumentTrait;

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    // Must be single and must be a decimal.
    if (is_numeric($this->argument) && $this->argument > 0) {
      $query = $this->getQuery();
      $location_options = (array) $query->getOption('search_api_location');
      $add_options = [
        'radius' => $this->argument,
      ];
      // We currently have no way of knowing what part of the field name was
      // added to have the distance pseudo field name.
      // So for now we remove '__distance' from the field name to get the
      // location field.
      /* @see search_api_location_views_views_data_alter */
      $location_field_name = str_replace('__distance', '', $this->realField);

      $this->addFieldOptions($location_options, $add_options, $location_field_name);
      $query->setOption('search_api_location', $location_options);
    }
  }

}
