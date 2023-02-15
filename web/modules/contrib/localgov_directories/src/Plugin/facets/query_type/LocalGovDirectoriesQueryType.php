<?php

namespace Drupal\localgov_directories\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\search_api\Query\QueryInterface;

/**
 * AND facet groups while keeping the operator within a facets as an OR.
 *
 * @FacetsQueryType(
 *   id = "localgov_directories_query_type",
 *   label = @Translation("LocalGov Directories Facet Groups AND Query Type"),
 * )
 */
class LocalGovDirectoriesQueryType extends QueryTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = $this->query;

    // Only alter the query when there's an actual query object to alter.
    if (!empty($query)) {
      $operator = $this->facet->getQueryOperator();
      $field_identifier = $this->facet->getFieldIdentifier();
      $exclude = $this->facet->getExclude();

      if ($query->getProcessingLevel() === QueryInterface::PROCESSING_FULL) {
        // Set the options for the actual query.
        $options = &$query->getOptions();
        $options['search_api_facets'][$field_identifier] = $this->getFacetOptions();
      }

      // Add the filter to the query if there are active values.
      $active_items = $this->facet->getActiveItems();

      if (count($active_items)) {

        $type_storage = \Drupal::entityTypeManager()
          ->getStorage('localgov_directories_facets');
        $chosen_facets = $type_storage->loadMultiple($active_items);
        foreach ($chosen_facets as $directory_facet) {
          $bundle[$directory_facet->bundle()][] = $directory_facet->id();
        }

        $filter = NULL;
        foreach ($bundle as $bundle_name => $group_items) {
          unset($filter);
          $filter = $query->createConditionGroup($operator, ['facet:' . $field_identifier . '.' . $bundle_name]);
          foreach ($group_items as $value) {
            $filter->addCondition($this->facet->getFieldIdentifier(), $value, $exclude ? '<>' : '=');
          }
          $query->addConditionGroup($filter);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query_operator = $this->facet->getQueryOperator();

    if (!empty($this->results)) {
      $facet_results = [];
      foreach ($this->results as $result) {
        if ($result['count'] || $query_operator === 'or') {
          $result_filter = $result['filter'] ?? '';
          if ($result_filter[0] === '"') {
            $result_filter = substr($result_filter, 1);
          }
          if ($result_filter[strlen($result_filter) - 1] === '"') {
            $result_filter = substr($result_filter, 0, -1);
          }
          $count = $result['count'];
          $result = new Result($this->facet, $result_filter, $result_filter, $count);
          $facet_results[] = $result;
        }
      }
      $this->facet->setResults($facet_results);
    }

    return $this->facet;
  }

}
