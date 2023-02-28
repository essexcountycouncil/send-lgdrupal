<?php

namespace Drupal\node\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\node\NodeInterface;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:node",
 *   label = @Translation("Node selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 1
 * )
 */
class NodeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'node_access' tag is sadly insufficient for nodes: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is there,
    // it is supposed to handle this check.
    if ($this->currentUser->hasPermission('bypass node access') || $this->moduleHandler->hasImplementations('node_grants')) {
      return $query;
    }

    // Permission to "view own unpublished content" allows
    // the user to reference any published content or own unpublished content.
    // Permission to "view any unpublished content" allows
    // the user to reference any unpublished content.
    if ($this->currentUser->hasPermission('view own unpublished content') && !$this->currentUser->hasPermission('view any unpublished content')) {
      $or = $query->orConditionGroup()
        ->condition('status', NodeInterface::PUBLISHED)
        ->condition('uid', $this->currentUser->id());
      $query->condition($or);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if ($this->currentUser->hasPermission('bypass node access') || $this->moduleHandler->hasImplementations('node_grants')) {
      return $entities;
    }

    // Permission to "view own unpublished content" allows
    // the user to reference any published content or own unpublished content.
    if ($this->currentUser->hasPermission('view own unpublished content') && !$this->currentUser->hasPermission('view any unpublished content')) {
      $entities = array_filter($entities, function ($node) {
        /** @var \Drupal\node\NodeInterface $node */
        return ($node->getOwnerId() == $this->currentUser->id() || $node->isPublished());
      });
    }

    return $entities;
  }

}
