<?php

namespace Drupal\webform_workflows_element_maestro\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscribe to the Route to change page titles.
 */
class RouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('maestro.execute')) {
      $route->setDefault('_title_callback', 'webform_workflows_element_maestro_execute_title');
    }
  }
}
