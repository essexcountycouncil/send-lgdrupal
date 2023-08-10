<?php

namespace Drupal\localgov_services_status;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class for fetching service statuses.
 *
 * @package Drupal\localgov_services_status
 */
class ServiceStatus {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Initialise a ServiceStatus instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity Type Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Returns the latest 2 status updates for the service landing page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Service landing page node to get status pages for.
   *
   * @return array
   *   Array of item variables to render in Twig template.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getStatusForBlock(Node $node) {
    return $this->getStatusUpdates($node, 2, FALSE, TRUE);
  }

  /**
   * Returns the latest 10 status updates for the service landing page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Service landing page node to get status pages for.
   *
   * @return array
   *   Array of item variables to render in Twig template.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getStatusForPage(Node $node) {
    return $this->getStatusUpdates($node, 10, TRUE, FALSE);
  }

  /**
   * Returns the latest $n status updates for the service landing page.
   *
   * @param \Drupal\node\NodeInterface $landing_node
   *   Service landing page node to get status pages for.
   * @param int $n
   *   Number of status updates to fetch.
   * @param bool $hide_from_list
   *   Whether the statuses array should include items hidden from lists.
   * @param bool $hide_from_landing
   *   Whether the statuses array should include items hidden on landing pages.
   *
   * @return array
   *   Array of n items to render in Twig template.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getStatusUpdates(NodeInterface $landing_node, $n, $hide_from_list = FALSE, $hide_from_landing = FALSE) {
    $query = $this->statusUpdatesQuery($landing_node->id(), $hide_from_list, $hide_from_landing);
    $result = $query->sort('created', 'DESC')
      ->range(0, $n)
      ->execute();
    $service_status = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple($result);

    $items = [];
    foreach ($service_status as $node) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $this->entityRepository->getTranslationFromContext($node);
      if ($node->hasField('body') && $body = $node->get('body')->first()) {
        $summary = $body->getValue()['summary'];
      }
      else {
        $summary = '';
      }
      $items[] = [
        'date' => $node->getCreatedTime(),
        'title' => $node->label(),
        'description' => $summary,
        'url' => $node->toUrl(),
      ];
    }
    return $items;
  }

  /**
   * Returns the item count for a status updates list.
   *
   * @param \Drupal\node\NodeInterface $landing_node
   *   Service landing page node to get status pages for.
   * @param bool $hide_from_list
   *   Whether the statuses array should include items hidden from lists.
   * @param bool $hide_from_landing
   *   Whether the statuses array should include items hidden on landing pages.
   *
   * @return int
   *   Number of items.
   */
  public function statusUpdateCount(NodeInterface $landing_node, $hide_from_list, $hide_from_landing): int {
    $query = $this->statusUpdatesQuery($landing_node->id(), $hide_from_list, $hide_from_landing);
    return $query->count()->execute();
  }

  /**
   * Base query for status updates.
   *
   * @param int $landing_nid
   *   Landing Node Id.
   * @param bool $hide_from_list
   *   If to include status updates excluded from the list.
   * @param bool $hide_from_landing
   *   Whether the statuses array should include items hidden on landing pages.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Partly prepared Entity Query.
   */
  protected function statusUpdatesQuery($landing_nid, $hide_from_list, $hide_from_landing) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'localgov_services_status')
      ->condition('localgov_services_parent', $landing_nid)
      ->condition('status', NodeInterface::PUBLISHED)
      ->addTag('node_access')
      ->accessCheck(TRUE);
    if ($hide_from_list) {
      $query->condition('localgov_service_status_on_list', 1);
    }
    if ($hide_from_landing) {
      $query->condition('localgov_service_status_on_landi', 1);
    }

    return $query;
  }

}
