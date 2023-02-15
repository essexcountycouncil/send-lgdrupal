<?php

namespace Drupal\preview_link\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Preview link controller to view any entity.
 */
class PreviewLinkController extends ControllerBase {

  /**
   * Preview any entity with the default view mode.
   *
   * @return array
   *   A render array for previewing the entity.
   */
  public function preview() {
    $entity = $this->resolveEntity();
    return $this->entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity);
  }

  /**
   * Preview page title.
   *
   * @return string
   *   The title of the entity.
   */
  public function title() {
    return $this->resolveEntity()->label();
  }

  /**
   * Resolve the entity being previewed.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  protected function resolveEntity() {
    $route_match = \Drupal::routeMatch();
    $preview_link_paramater = $route_match->getRouteObject()->getOption('preview_link.entity_type_id');
    return $route_match->getParameter($preview_link_paramater);
  }

}
