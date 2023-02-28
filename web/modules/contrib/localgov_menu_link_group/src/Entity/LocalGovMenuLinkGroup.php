<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the LocalGovMenuLinkGroup **config** entity.
 *
 * @ConfigEntityType(
 *   id = "localgov_menu_link_group",
 *   label = @Translation("LocalGov menu link group"),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\localgov_menu_link_group\Form\LocalGovMenuLinkGroupForm",
 *       "add" = "Drupal\localgov_menu_link_group\Form\LocalGovMenuLinkGroupForm",
 *       "edit" = "Drupal\localgov_menu_link_group\Form\LocalGovMenuLinkGroupForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\localgov_menu_link_group\Controller\LocalGovMenuLinkGroupListBuilder",
 *   },
 *   config_prefix = "localgov_menu_link_group",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "group_label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "group_label",
 *     "weight",
 *     "parent_menu",
 *     "parent_menu_link",
 *     "child_menu_links",
 *   },
 *   links = {
 *     "collection"  = "/admin/structure/menu/localgov_menu_link_group",
 *     "add-form"    = "/admin/structure/menu/localgov_menu_link_group/add",
 *     "edit-form"   = "/admin/structure/menu/localgov_menu_link_group/{localgov_menu_link_group}",
 *     "delete-form" = "/admin/structure/menu/localgov_menu_link_group/{localgov_menu_link_group}/delete",
 *   }
 * )
 */
class LocalGovMenuLinkGroup extends ConfigEntityBase implements LocalGovMenuLinkGroupInterface {

  /**
   * The entity id.
   *
   * @var string
   */
  public $id;

  /**
   * Group name.
   *
   * This label used to prepare a menu link for this group.
   *
   * @var string
   */
  public $group_label;

  /**
   * Weight of the group's menu link.
   *
   * @var string
   */
  public $weight = 0;

  /**
   * Parent menu of the group.
   *
   * @var string
   */
  public $parent_menu = 'admin';

  /**
   * Parent menu link of the group.
   *
   * @var string
   */
  public $parent_menu_link = 'system.admin_content';

  /**
   * Child menu links of the group.
   *
   * @var string
   */
  public $child_menu_links = [];

  /**
   * Use numeric keys for array (i.e. sequence) type config values.
   *
   * We cannot have dots in key names when the config value is of type array.
   * Using numeric keys saves us from the trouble to deal with dotted key names.
   * Example:
   * - Invalid value: ['foo.bar' => 'Foo bar']
   * - Valid value: ['Foo bar']
   *
   * @see Drupal/Core/Config/ConfigBase::validateKeys()
   */
  public function set($property_name, $value) {

    if ($property_name === 'child_menu_links') {
      $value = array_values($value);
    }

    parent::set($property_name, $value);
  }

}
