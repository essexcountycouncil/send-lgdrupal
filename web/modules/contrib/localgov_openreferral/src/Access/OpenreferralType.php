<?php

namespace Drupal\localgov_openreferral\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\localgov_openreferral\MappingInformation;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on mapped Open Referral type.
 */
class OpenreferralType implements AccessInterface {

  /**
   * Mapping information service.
   *
   * @var \Drupal\localgov_openreferral\MappingInformation
   */
  protected $mappingInformation;

  /**
   * Access constructor.
   *
   * @param \Drupal\localgov_openreferral\MappingInformation $mapping_information
   *   Mapping information helper service.
   */
  public function __construct(MappingInformation $mapping_information) {
    $this->mappingInformation = $mapping_information;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match) {
    [$parameter, $type] = explode(':', $route->getRequirement('_openreferral_type'));

    $parameters = $route_match->getParameters();
    if ($parameters->has($parameter)) {
      $entity = $parameters->get($parameter);
      $mapping = $this->mappingInformation->getPublicType($entity->getEntityTypeId(), $entity->bundle());
      if ($mapping === $type) {
        return AccessResult::allowed()->addCacheableDependency($entity);
      }
    }

    return AccessResult::neutral('The entity does not match the Open Referral type.');
  }

}
