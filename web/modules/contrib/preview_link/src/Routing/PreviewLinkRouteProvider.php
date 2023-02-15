<?php

namespace Drupal\preview_link\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Preview Link route provider.
 */
class PreviewLinkRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    if ($route = $this->getGeneratePreviewLinkRoute($entity_type)) {
      $entity_type_id = $entity_type->id();
      $collection->add("entity.{$entity_type_id}.generate_preview_link", $route);
    }

    if ($route = $this->getPreviewLinkRoute($entity_type)) {
      $entity_type_id = $entity_type->id();
      $collection->add("entity.{$entity_type_id}.preview_link", $route);
    }

    return $collection;
  }

  /**
   * Gets the route for generating and viewing preview links for this entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getGeneratePreviewLinkRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->hasLinkTemplate('canonical')) {
      return NULL;
    }

    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('canonical') . '/generate-preview-link');

    $route
      ->setDefaults([
        '_entity_form' => "preview_link.preview_link",
        '_title' => 'Preview',
      ])
      ->setRequirement('_permission', 'generate preview links')
      ->setRequirement('_access_preview_enabled', 'TRUE')
      ->setOption('preview_link.entity_type_id', $entity_type_id)
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);

    return $route;
  }

  /**
   * Gets the preview link route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getPreviewLinkRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->hasLinkTemplate('canonical')) {
      return NULL;
    }

    $entity_type_id = $entity_type->id();
    $route = new Route('/preview-link/' . $entity_type_id . '/{' . $entity_type_id . '}/{preview_token}');

    $route
      ->setDefaults([
        '_controller' => 'Drupal\preview_link\Controller\PreviewLinkController::preview',
        '_title_callback' => 'Drupal\preview_link\Controller\PreviewLinkController::title',
      ])
      ->setRequirement('_entity_access', $entity_type_id . '.view')
      ->setRequirement('_access_preview_enabled', 'TRUE')
      ->setOption('preview_link.entity_type_id', $entity_type_id)
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id, 'load_latest_revision' => TRUE],
        'preview_token' => ['type' => 'string'],
      ])
      ->setOption('_preview_link_route', TRUE);

    return $route;
  }

}
