<?php

namespace Drupal\localgov_openreferral\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer as ViewsSerializer;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "openreferral_serializer",
 *   title = @Translation("Open Referral serializer"),
 *   help = @Translation("Serializes views row data and pager in Open Referral format."),
 *   display_types = {"data"}
 * )
 */
class Serializer extends ViewsSerializer {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'pager_serializer.settings';

  /**
   * Pager None class.
   *
   * @var string
   */
  const PAGER_NONE = 'Drupal\views\Plugin\views\pager\None';

  /**
   * Pager Some class.
   *
   * @var string
   */
  const PAGER_SOME = 'Drupal\views\Plugin\views\pager\Some';

  /**
   * {@inheritdoc}
   */
  public function render() {

    $rows = [];

    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if (method_exists($this->displayHandler, 'getContentType')) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    $pagination = $this->pagination($rows);
    $result = $pagination;
    $result['content'] = $rows;

    return $this->serializer->serialize($result, $content_type, ['views_style_plugin' => $this]);
  }

  /**
   * {@inheritdoc}
   */
  protected function pagination($rows) {

    $pagination = [];
    $current_page = 0;
    $items_per_page = 0;
    $total_items = 0;
    $total_pages = 1;
    $class = NULL;

    $pager = $this->view->pager;

    if ($pager) {
      $items_per_page = $pager->getItemsPerPage();
      $total_items = $pager->getTotalItems();
      $class = get_class($pager);
    }

    if (method_exists($pager, 'getPagerTotal')) {
      $total_pages = $pager->getPagerTotal();
    }
    if (method_exists($pager, 'getCurrentPage')) {
      $current_page = $pager->getCurrentPage();
    }
    if ($class == static::PAGER_NONE) {
      $items_per_page = $total_items;
    }
    elseif ($class == static::PAGER_SOME) {
      $total_items = count($rows);
    }
    // Open Referral counts from 1, Drupal from 0.
    $current_page++;

    $pagination['totalElements'] = $total_items;
    $pagination['totalPages'] = $total_pages;
    $pagination['number'] = $current_page;
    $pagination['size'] = $items_per_page;
    $pagination['first'] = $current_page == 1;
    $pagination['last'] = $current_page == $total_pages;

    return $pagination;
  }

}
