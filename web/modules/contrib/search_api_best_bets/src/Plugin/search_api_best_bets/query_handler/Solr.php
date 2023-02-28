<?php

namespace Drupal\search_api_best_bets\Plugin\search_api_best_bets\query_handler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api_best_bets\QueryHandler\QueryHandlerPluginBase;
use Drupal\search_api_best_bets\QueryHandler\QueryHandlerPluginInterface;
use Drupal\search_api_solr\Utility\Utility;

/**
 * Provides a Solr best bets handler plugin.
 *
 * @SearchApiBestBetsQueryHandler(
 *   id = "solr",
 *   label = @Translation("Solr"),
 *   description = @Translation("Adds Best Bets support to Apache Solr using elevate and exclude query parameters."),
 *   backends = {
 *     "search_api_solr"
 *   }
 * )
 */
class Solr extends QueryHandlerPluginBase implements QueryHandlerPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery(array $items, QueryInterface &$query) {
    $index = $query->getIndex()->id();

    // Process each entity to get the Solr Document ID.
    $query_item_ids = [];
    foreach ($items as $key => $item_ids) {
      foreach ($item_ids as $item_id) {
        $query_item_ids[$key][] = $this->generateSolrItemId($item_id, $index);
      }
    }

    $query->setOption('solr_param_forceElevation', 'true');
    $query->setOption('solr_param_enableElevation', 'true');

    // Add the documents ids to be elevated to the search query.
    if (isset($query_item_ids['elevate']) && is_array($query_item_ids['elevate'])) {
      $query->setOption('solr_param_elevateIds', implode(',', $query_item_ids['elevate']));
    }

    // Add the documents ids to be excluded to the search query.
    if (isset($query_item_ids['exclude']) && is_array($query_item_ids['exclude'])) {
      $query->setOption('solr_param_excludeIds', implode(',', $query_item_ids['exclude']));
    }
  }

  /**
   * Generate the Solr Document ID.
   *
   * @todo Get the Search API Item ID from Search API instead of generating
   * @todo it our selves.
   *
   * @param string $item_id
   *   The Search API Item ID.
   * @param string $index
   *   THe Solr index name.
   *
   * @return string
   *   The generated Solr document id.
   */
  private function generateSolrItemId($item_id, $index) {
    $pieces = [
      Utility::getSiteHash(),
      '-',
      $index,
      '-',
      $item_id,
    ];

    return implode('', $pieces);
  }

  /**
   * {@inheritdoc}
   */
  public function alterResults(ResultSetInterface &$results) {
    $solr_response = $results->getExtraData('search_api_solr_response')['response'];
    $items = $results->getResultItems();

    $elevated_items = [];
    if (isset($solr_response['docs']) && is_array($solr_response['docs'])) {
      // Convert Solr response to simple array.
      foreach ($solr_response['docs'] as $doc) {
        if (isset($doc['[elevated]']) && $doc['[elevated]']) {
          $elevated_items[$doc['ss_search_api_id']] = TRUE;
        }
      }

      // Process items and add the extra elevate data.
      foreach ($items as $item) {
        if ($item->getId() && isset($elevated_items[$item->getId()])) {
          $item->setExtraData('elevated', TRUE);
          $results->addResultItem($item);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
