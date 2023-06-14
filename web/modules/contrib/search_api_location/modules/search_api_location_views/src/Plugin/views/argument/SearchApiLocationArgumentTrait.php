<?php

namespace Drupal\search_api_location_views\Plugin\views\argument;

/**
 * Provides common methods for Search API Location contextual filters.
 */
trait SearchApiLocationArgumentTrait {

  /**
   * Adds a location filter to an existing "search_api_location" array.
   *
   * @param array $original_options
   *   The existing options.
   * @param array $add_options
   *   The options to add.
   * @param string $field
   *   The field for which to add the options.
   */
  protected function addFieldOptions(array &$original_options, array $add_options, $field) {
    foreach ($original_options as &$field_options) {
      if ($field_options['field'] == $field) {
        // Found existing filter. Add our options and return.
        $field_options = $add_options + $field_options;
        return;
      }
    }
    // Field not yet in options, create new element.
    $add_options['field'] = $field;
    $original_options[] = $add_options;
  }

}
