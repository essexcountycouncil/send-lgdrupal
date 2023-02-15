<?php

namespace Drupal\search_api_best_bets\QueryHandler;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class for best bets query handler plugin managers.
 *
 * @ingroup plugin_api
 */
class QueryHandlerPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_best_bets/query_handler', $namespaces, $module_handler, 'Drupal\search_api_best_bets\QueryHandler\QueryHandlerPluginInterface', 'Drupal\search_api_best_bets\Annotation\SearchApiBestBetsQueryHandler');
    $this->alterInfo('best_bets_query_handler_info');
    $this->setCacheBackend($cache_backend, 'best_bets_query_handler_info_plugins');
  }

  /**
   * Gets a list of available query handlers.
   *
   * @return array
   *   An array with the handler names as keys and the descriptions as values.
   */
  public function getAvailableQueryHandlers() {
    // Use plugin system to get list of available best bets handlers.
    $handlers = $this->getDefinitions();

    $output = [];
    foreach ($handlers as $id => $definition) {
      $output[$id] = $definition;
    }

    return $output;
  }

  /**
   * Gets a list of available query handlers.
   *
   * They support a specific search backend.
   *
   * @param string $backend
   *   The backend plugin id.
   *
   * @return array
   *   An array with the query handler names as keys and the descriptions as
   *   values.
   */
  public function getAvailableQueryHandlersByBackend($backend) {
    $handlers = $this->getAvailableQueryHandlers();

    foreach ($handlers as $id => $definition) {
      if (!is_array($definition['backends']) || !in_array($backend, $definition['backends'])) {
        unset($handlers[$id]);
      }
    }

    return $handlers;
  }

  /**
   * Validate that the active query handler plugin exists.
   *
   * @param string $plugin
   *   The plugin machine name.
   *
   * @return bool
   *   True if the plugin exists.
   */
  public function validatePlugin($plugin) {
    if ($this->getDefinition($plugin, FALSE)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
