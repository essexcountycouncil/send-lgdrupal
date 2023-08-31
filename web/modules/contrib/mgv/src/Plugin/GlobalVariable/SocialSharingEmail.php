<?php

namespace Drupal\mgv\Plugin\GlobalVariable;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\mgv\Plugin\GlobalVariable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SocialSharingEmail.
 *
 * Social Sharing Global Variables
 * To use this, you need to wrap the variable in an anchor tag, such as:
 * ```
 * <a href="{{ global_variables.social_sharing.email }}">Email</a>
 * ```
 * Share the current page by Email.
 *
 * @package Drupal\mgv\Plugin\GlobalVariable
 *
 * @Mgv(
 *   id = "social_sharing\email",
 *   variableDependencies={
 *     "current_page_title",
 *     "site_name",
 *   }
 * );
 */
class SocialSharingEmail extends GlobalVariable implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * Constructs Social Sharing Email plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack instance.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    protected RouteMatchInterface $routeMatch,
    protected RequestStack $requestStack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return Url::fromUri(
      'mailto:',
      [
        'query' => [
          'subject' => $this->getDependency('current_page_title'),
          'body' => $this->t(
            'Check this out from @sitename: :base_url:current_path',
            [
              '@sitename' => $this->getDependency('site_name'),
              ':base_url' => '',
              ':current_path' => Url::fromRoute(
                $this->routeMatch->getRouteName(),
                $this->routeMatch->getRawParameters()->all(),
                [
                  'query' => $this->requestStack->getCurrentRequest()->query->all(),
                  'absolute' => TRUE,
                ])->toString(),
            ]
          ),
        ],
      ])
      ->toUriString();
  }

}
