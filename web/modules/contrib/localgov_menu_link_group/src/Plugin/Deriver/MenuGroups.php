<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\localgov_menu_link_group\Entity\LocalGovMenuLinkGroupInterface;
use Drupal\localgov_menu_link_group\MenuLinkGrouper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Menu link generator.
 *
 * Generate one menu link for each localgov_menu_link_group config entity.
 */
class MenuGroups extends DeriverBase implements ContainerDeriverInterface {

  const MENU_LINK_GROUP_CONFIG_ENTITY = 'localgov_menu_link_group';

  /**
   * {@inheritdoc}
   *
   * Define menu links.  Each menu link represents a menu link group.
   *
   * Multiple group entities can belong to the same group.  This happens when
   * they have the same label and parent menu link.  In such circumstances, we
   * take the smallest weight from such group entities and use that while
   * preparing the group-specific menu link.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $active_menu_link_groups = $this->fetchActiveMenuLinkGroups();

    // The uniqueness of a group depends on its **parent menu link** and
    // **label**.
    $unique_groups = [];
    array_walk($active_menu_link_groups, function (LocalGovMenuLinkGroupInterface $group, string $ignore) use (&$unique_groups) {
      $menu_link_key = MenuLinkGrouper::prepareMenuLinkIndexForGroup($group);
      $unique_groups[$menu_link_key] = $unique_groups[$menu_link_key] ?? $group;
    });

    $unique_group_count = count($unique_groups);
    $menu_links = array_map(
      [$this, 'prepareMenuLinkForGroup'],
      $unique_groups,
      array_fill(0, $unique_group_count, $base_plugin_definition));

    $menu_links_with_keys = array_combine(array_keys($unique_groups), $menu_links);

    return $menu_links_with_keys;
  }

  /**
   * Define the menu link for **one** menu link group.
   *
   * The menu link's title is taken from the corresponding
   * localgov_menu_link_group entity.
   */
  public static function prepareMenuLinkForGroup(LocalGovMenuLinkGroupInterface $group, array $base_menu_link_definition): array {

    $menu_link_for_group = [
      'title'      => $group->label(),
      'menu_name'  => $group->get('parent_menu'),
      'parent'     => $group->get('parent_menu_link'),
      'weight'     => $group->get('weight'),
    ] + $base_menu_link_definition;

    return $menu_link_for_group;
  }

  /**
   * Load our localgov_menu_link_group entities.
   */
  protected function fetchActiveMenuLinkGroups(): array {

    $active_menu_link_group_ids = $this->entityTypeManager->getStorage(self::MENU_LINK_GROUP_CONFIG_ENTITY)->getQuery()->condition('status', TRUE)->sort('weight')->execute();
    $active_menu_link_groups = $this->entityTypeManager->getStorage(self::MENU_LINK_GROUP_CONFIG_ENTITY)->loadMultiple($active_menu_link_group_ids);

    return $active_menu_link_groups;
  }

  /**
   * Keep track of the dependencies.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Factory method.
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {

    return new static($container->get('entity_type.manager'));
  }

  /**
   * Entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

}
