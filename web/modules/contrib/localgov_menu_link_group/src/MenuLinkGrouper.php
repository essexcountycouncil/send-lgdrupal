<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group;

use Drupal\Component\Utility\Html;
use Drupal\localgov_menu_link_group\Entity\LocalGovMenuLinkGroupInterface;

/**
 * Reassign parent menu link for a group's child menu links.
 *
 * For each menu link group, we create a menu link.  All the child menu links
 * of this group then appear as children of this group menu link.  Here we
 * replace the original parent menu link of each child menu link with the group
 * menu link.
 *
 * Example: Child menu link A belongs to Group G.  A's original parent menu link
 * is B.  After reassignment, G will become A's new parent menu link.
 */
class MenuLinkGrouper {

  /**
   * Keep a **reference** to the entire menu link tree.
   *
   * @param array $menu_links
   *   This is the menu_links tree passed to hook_menu_links_discovered_alter().
   */
  public function __construct(array &$menu_links) {

    $this->menuLinks = &$menu_links;
  }

  /**
   * Reassign the parent menu link for all child menu links of a group.
   */
  public function groupChildMenuLinks(LocalGovMenuLinkGroupInterface $group, string $group_id): void {

    $group_menu_index = self::prepareMenuLinkIndexForGroup($group);

    $child_menu_links = $group->get('child_menu_links');
    array_walk($child_menu_links, [$this, 'setNewParentForChildMenuLink'], $group_menu_index);
  }

  /**
   * Reassign the parent menu link for a child menu link of a group.
   */
  public function setNewParentForChildMenuLink(string $child_menu_link_id, string $ignore, string $group_menu_index): void {

    $is_unknown_child_menu_link = !array_key_exists($child_menu_link_id, $this->menuLinks);
    if ($is_unknown_child_menu_link) {
      return;
    }

    $group_menu_link_id = "localgov_menu_link_group:$group_menu_index";
    $this->menuLinks[$child_menu_link_id]['parent'] = $group_menu_link_id;
  }

  /**
   * Use the parent menu link and group label to prepare a menu link index.
   *
   * Example:
   * - Group label: Service
   * - Parent menu link: system.admin_config_development
   * - Menu link index: system.admin_config_development:service.
   *
   * Note that the menu link index is not the same as the menu link id.  It
   * forms a part of the full menu link id.
   */
  public static function prepareMenuLinkIndexForGroup(LocalGovMenuLinkGroupInterface $group): string {

    $machine_name_for_label = str_replace('-', '_', Html::getClass($group->label()));
    $menu_link_index = "{$group->get('parent_menu_link')}:{$machine_name_for_label}";

    return $menu_link_index;
  }

  /**
   * The entire menu tree of a Drupal instance.
   *
   * The menu_links tree passed to hook_menu_links_discovered_alter().
   *
   * @var array
   */
  protected $menuLinks;

}
