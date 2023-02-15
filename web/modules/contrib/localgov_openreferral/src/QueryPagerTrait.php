<?php

namespace Drupal\localgov_openreferral;

use Drupal\Core\Entity\Query\QueryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Adds a Entity Query trait for paging based on Open Referral standard.
 */
trait QueryPagerTrait {

  /**
   * The pager data.
   *
   * @var array
   */
  protected $pager = [];

  /**
   * Add the pager to an Entity Query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to page.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $parameters
   *   The request parameters.
   */
  public function initializePager(QueryInterface $query, ParameterBag $parameters) {
    $page = $parameters->getInt('page', 1);
    $this->pager['page'] = $page - 1;

    $per_page = $parameters->getInt('per_page', 50);
    $this->pager['limit'] = $per_page;

    $count_query = clone $query;
    $this->pager['total'] = $count_query->count()->accessCheck(TRUE)->execute();
    $this->pager['start'] = $this->pager['page'] * $this->pager['limit'];

    $query->range($this->pager['start'], $this->pager['limit']);
  }

  /**
   * Return pager data.
   *
   * @return array
   *   The pager.
   */
  public function outputPager() {
    $total_pages = ceil($this->pager['total'] / $this->pager['limit']);
    $page_number = $this->pager['page'] + 1;
    return [
      'totalElements' => $this->pager['total'],
      'totalPages' => $total_pages,
      'number' => $page_number,
      'size' => $this->pager['limit'],
      'first' => $this->pager['page'] == 0,
      'last' => $page_number == $total_pages,
    ];
  }

}
