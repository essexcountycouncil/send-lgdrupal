<?php

namespace Drupal\preview_link\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;

/**
 * Preview link access check.
 */
class PreviewEnabledAccessCheck implements AccessInterface {

  /**
   * The module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * PreviewEnabledAccessCheck constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('preview_link.settings');
  }

  /**
   * Checks access to both the generate route and the preview route.
   */
  public function access(Route $route, RouteMatchInterface $route_match) {
    // Get the entity for both the preview route and the generate preview link
    // route.
    if ($entity_type_id = $route->getOption('preview_link.entity_type_id')) {
      $entity = $route_match->getParameter($route->getOption('preview_link.entity_type_id'));
    }
    else {
      $entity = $route_match->getParameter('entity');
    }

    return AccessResult::allowedIf($this->entityTypeAndBundleEnabled($entity))
      ->addCacheableDependency($entity)
      ->addCacheContexts(['route'])
      ->addCacheableDependency($this->config);
  }

  /**
   * Check if the entity type and bundle are enabled.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  protected function entityTypeAndBundleEnabled(EntityInterface $entity) {
    $enabled_entity_types = $this->config->get('enabled_entity_types');

    // If no entity types are specified, fallback to allowing all.
    if (empty($enabled_entity_types)) {
      return TRUE;
    }

    // If the entity type exists in the configuration object.
    if (isset($enabled_entity_types[$entity->getEntityTypeId()])) {
      $enabled_bundles = $enabled_entity_types[$entity->getEntityTypeId()];
      // If no bundles were specified, assume all bundles are enabled.
      if (empty($enabled_bundles)) {
        return TRUE;
      }
      // Otherwise fallback to requiring the specific bundle.
      if (in_array($entity->bundle(), $enabled_bundles)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
