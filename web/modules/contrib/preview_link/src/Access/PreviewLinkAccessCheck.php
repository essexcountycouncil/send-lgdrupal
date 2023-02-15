<?php

namespace Drupal\preview_link\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Preview link access check.
 */
class PreviewLinkAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PreviewLinkAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Checks access to the node add page for the node type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $preview_token
   *   The preview token.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   A \Drupal\Core\Access\AccessInterface value.
   */
  public function access(EntityInterface $entity = NULL, $preview_token = NULL) {
    $neutral = AccessResult::neutral()
      ->addCacheableDependency($entity)
      ->addCacheContexts(['preview_link_route']);
    if (!$preview_token || !$entity) {
      return $neutral;
    }

    /** @var \Drupal\preview_link\Entity\PreviewLinkInterface $preview_link */
    $preview_link = $this->entityTypeManager->getStorage('preview_link')->getPreviewLink($entity);

    // If we can't find a valid preview link then ignore this.
    if (!$preview_link) {
      return $neutral->setReason('This entity does not have a preview link.');
    }

    // If an entity has a preview link and it doesnt match up, then explicitly
    // deny access.
    if ($preview_token !== $preview_link->getToken()) {
      return AccessResult::forbidden('Preview token is invalid.')
        ->addCacheableDependency($entity)
        ->addCacheContexts(['preview_link_route']);
    }

    return AccessResult::allowed()
      ->addCacheableDependency($entity)
      ->addCacheableDependency($preview_link)
      ->addCacheContexts(['preview_link_route']);
  }

}
