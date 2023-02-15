<?php

namespace Drupal\localgov_geo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Geo type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "localgov_geo_type",
 *   label = @Translation("Geo type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\localgov_geo\Form\LocalgovGeoTypeForm",
 *       "edit" = "Drupal\localgov_geo\Form\LocalgovGeoTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\localgov_geo\LocalgovGeoTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer geo types",
 *   bundle_of = "localgov_geo",
 *   config_prefix = "localgov_geo_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/geo_types/add",
 *     "edit-form" = "/admin/structure/geo_types/manage/{localgov_geo_type}",
 *     "delete-form" = "/admin/structure/geo_types/manage/{localgov_geo_type}/delete",
 *     "collection" = "/admin/structure/geo_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "label_token",
 *   }
 * )
 */
class LocalgovGeoType extends ConfigEntityBundleBase {

  /**
   * The machine name of this geo type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the geo type.
   *
   * @var string
   */
  protected $label;

  /**
   * The token string to use to replace for entity label default.
   *
   * @var string
   */
  protected $label_token;

  /**
   * Return the label token.
   */
  public function labelToken() {
    return $this->label_token;
  }

}
