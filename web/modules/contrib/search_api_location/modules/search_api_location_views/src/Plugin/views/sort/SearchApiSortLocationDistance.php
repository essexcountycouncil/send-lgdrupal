<?php

namespace Drupal\search_api_location_views\Plugin\views\sort;

use Drupal\search_api\Plugin\views\sort\SearchApiSort;

/**
 * Provides a location distance sort plugin for Search API views.
 *
 * @ViewsSort("search_api_location_distance")
 */
class SearchApiSortLocationDistance extends SearchApiSort {

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->query->getOption('search_api_location')) {
      parent::query();
    }
  }

}
