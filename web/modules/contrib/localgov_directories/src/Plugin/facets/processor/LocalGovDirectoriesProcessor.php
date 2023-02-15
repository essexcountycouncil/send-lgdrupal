<?php

namespace Drupal\localgov_directories\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * ANDs LocalGov Directories Facet Groups while keeping OR within each group.
 *
 * @FacetsProcessor(
 *   id = "localgov_directories_processor",
 *   label = @Translation("LocalGov Directories - AND Facet Groups"),
 *   description = @Translation("ANDs LocalGov Directories Facet Groups while keeping OR within each group."),
 *   stages = {
 *     "pre_query" = 35
 *   }
 * )
 */
class LocalGovDirectoriesProcessor extends ProcessorPluginBase implements PreQueryProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $active_items = $facet->getActiveItems();
    $facet->setActiveItems($active_items);
  }

  /**
   * {@inheritdoc}
   *
   * String corresponds to a key on the $query_types array as defined
   * within hook_facets_search_api_query_type_mapping_alter().
   *
   * @see hook_facets_search_api_query_type_mapping_alter()
   */
  public function getQueryType() {
    return 'localgov_directories';
  }

}
