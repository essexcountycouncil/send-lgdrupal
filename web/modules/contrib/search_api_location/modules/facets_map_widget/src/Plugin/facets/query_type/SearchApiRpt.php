<?php

namespace Drupal\facets_map_widget\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;

/**
 * Provides support for location facets within the Search API scope.
 *
 * This query type supports SpatialRecursivePrefixTree data type. This specific
 * implementation of the query type supports a generic solution of
 * adding an interactive map facets showing clustered heatmap.
 *
 * @FacetsQueryType(
 *   id = "search_api_rpt",
 *   label = @Translation("RecursivePrefixTree Type"),
 * )
 */
class SearchApiRpt extends QueryTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = $this->query;
    $field_identifier = $this->facet->getFieldIdentifier();

    // Set the options for the actual query.
    $options = &$query->getOptions();

    $options['search_api_facets'][$field_identifier] = [
      'field' => $field_identifier,
      'limit' => $this->facet->getHardLimit(),
      'operator' => $this->facet->getQueryOperator(),
      'min_count' => $this->facet->getMinCount(),
      'missing' => FALSE,
    ];

    // Bounding box coordinates which dynamically updated by panning or zooming
    // the map. By default its value is bounding box coordinates of whole world.
    $geom_value = '["-180 -90" TO "180 90"]';
    if (!empty($this->facet->getActiveItems())) {
      $geom_value = reset($this->facet->getActiveItems());
      $geom_value = str_replace(['(geom:', ')'], ['', ''], $geom_value);
    }

    $options['search_api_rpt'][$field_identifier] = [
      'field' => $field_identifier,
      'geom' => $geom_value,
      'gridLevel' => '2',
      'maxCells' => '35554432',
      'distErrPct' => '',
      'distErr' => '',
      'format' => 'ints2D',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query_operator = $this->facet->getQueryOperator();
    if (empty($this->results)) {
      return $this->facet;
    }

    $facet_results = [];
    foreach ($this->results as $result) {
      if ($result['count'] || $query_operator == 'or') {
        $count = $result['count'];
        $result = new Result($this->facet, $result['filter'], "heatmap", $count);
        $facet_results[] = $result;
      }
    }
    $this->facet->setResults($facet_results);
    return $this->facet;
  }

}
