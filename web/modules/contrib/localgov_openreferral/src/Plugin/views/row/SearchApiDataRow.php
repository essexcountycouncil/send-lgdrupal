<?php

namespace Drupal\localgov_openreferral\Plugin\views\row;

use Drupal\rest\Plugin\views\row\DataEntityRow;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Plugin which displays entities as raw data.
 *
 * @todo This is a temporary plugin:
 *   - We probably want to stop using views for the endpoint and make a route,
 *   but register it with facets as a source. Something like search_api_pages.
 *   - Alternatively this could be tested properly and go into search_api
 *   https://www.drupal.org/project/search_api/issues/2348117#comment-14152959
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "localgov_openreferral_data",
 *   title = @Translation("Entity (Search API)"),
 *   help = @Translation("Retrieves entities as row data."),
 *   display_types = {"data"}
 * )
 */
class SearchApiDataRow extends DataEntityRow {

  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $base_table = $view->storage->get('base_table');
    $this->index = SearchApiQuery::getIndexFromTable($base_table, $this->getEntityTypeManager());
    if (!$this->index) {
      $view_label = $view->storage->label();
      throw new \InvalidArgumentException("View '$view_label' is not based on Search API but tries to use its row plugin.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $datasource_id = $row->search_api_datasource;

    if (!($row->_object instanceof ComplexDataInterface)) {
      $context = [
        '%item_id' => $row->search_api_id,
        '%view' => $this->view->storage->label(),
      ];
      $this->getLogger()->warning('Failed to load item %item_id in view %view.', $context);
      return '';
    }

    if (!$this->index->isValidDatasource($datasource_id)) {
      $context = [
        '%datasource' => $datasource_id,
        '%view' => $this->view->storage->label(),
      ];
      $this->getLogger()->warning('Item of unknown datasource %datasource returned in view %view.', $context);
      return '';
    }

    $value = $row->_object->getValue();
    return $value instanceof EntityInterface ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

}
