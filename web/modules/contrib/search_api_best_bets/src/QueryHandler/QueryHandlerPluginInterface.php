<?php

namespace Drupal\search_api_best_bets\QueryHandler;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Provides an interface for best best query handler plugins.
 *
 * @ingroup plugin_api
 */
interface QueryHandlerPluginInterface extends PluginFormInterface, ConfigurableInterface {

  /**
   * Alter the search query.
   *
   * E.g. for adding elevate / exclude parameters.
   *
   * @param array $entities
   *   An array containing a list of entities matching with configured
   *   best bets matching the search query.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The Search API query object.
   */
  public function alterQuery(array $entities, QueryInterface &$query);

  /**
   * Alter results after receiving them from the search backend.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The Search API result object.
   */
  public function alterResults(ResultSetInterface &$results);

}
