<?php

namespace Drupal\localgov_services_status\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\localgov_services_status\ServiceStatus;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service status page controller.
 *
 * @package Drupal\localgov_services_status\Controller
 */
class ServiceStatusPageController extends ControllerBase {

  /**
   * Service status.
   *
   * @var \Drupal\localgov_services_status\ServiceStatus
   */
  protected $serviceStatus;

  /**
   * Constructs a new ServiceStatusPageController object.
   *
   * @param \Drupal\localgov_services_status\ServiceStatus $service_status
   *   The state service.
   */
  public function __construct(ServiceStatus $service_status) {
    $this->serviceStatus = $service_status;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('localgov_services_status.service_status')
    );
  }

  /**
   * Access check for landing page status listing page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Service node.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed for landing pages with service status list on.
   */
  public function access(NodeInterface $node): AccessResult {
    return AccessResult::allowedIf(
      $node->bundle() == 'localgov_services_landing' &&
      $this->serviceStatus->statusUpdateCount($node, TRUE, FALSE)
    );
  }

  /**
   * Build service status page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Service node.
   *
   * @return array
   *   A render array.
   */
  public function build(Node $node) {
    $build = [];

    $build[] = [
      '#theme' => 'page_header',
      '#title' => $this->t('Latest service updates'),
    ];

    $build[] = [
      '#theme' => 'service_status_page',
      '#items' => $this->serviceStatus->getStatusForPage($node),
    ];

    $build['#cache'] = [
      'contexts' => ['user.permissions', 'languages'],
      'tags' => ['node:' . $node->id(), 'node_list'],
    ];

    return $build;
  }

}
