<?php

namespace Drupal\localgov_openreferral\Pager;

use Drupal\Core\Pager\PagerParametersInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Accounts for Open Referral pager starting at 1.
 *
 * @see \Drupal\Core\Pager\PagerParameters
 */
class PagerParametersDecorator implements PagerParametersInterface {

  /**
   * The HTTP request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The pager parameters service being decorated.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected $pagerParameters;

  /**
   * Construct a PagerManager object.
   *
   * @param \Drupal\Core\Pager\PagerParametersInterface $pager_parameters
   *   The pager parameters service being decorated.
   * @param \Symfony\Component\HttpFoundation\RequestStack $stack
   *   The current HTTP request stack.
   */
  public function __construct(PagerParametersInterface $pager_parameters, RequestStack $stack) {
    $this->requestStack = $stack;
    $this->pagerParameters = $pager_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryParameters() {
    return $this->pagerParameters->getQueryParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function findPage($pager_id = 0) {
    // If an openreferral path, the first pager and not on the default page 0,
    // then reduce the page by one.
    $page = $this->pagerParameters->findPage($pager_id);
    if (strpos($this->requestStack->getCurrentRequest()->getPathInfo(), '/openreferral/v1/') === 0 && $pager_id == 0 && $page > 0) {
      $page--;
    }
    return $page;
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerQuery() {
    return $this->pagerParameters->getPagerQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerParameter() {
    return $this->pagerParameters->getPagerQuery();
  }

}
