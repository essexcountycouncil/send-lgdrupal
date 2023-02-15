<?php

namespace Drupal\preview_link;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a class for a preview link cache context.
 */
class PreviewLinkCacheContext implements CacheContextInterface {

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PreviewLinkCacheContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match.
   */
  public function __construct(RouteMatchInterface $routeMatch) {
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return 'Is preview link route';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return ($route = $this->routeMatch->getRouteObject()) && $route->getOption('_preview_link_route') ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return (new CacheableMetadata())->addCacheTags(['routes']);
  }

}
