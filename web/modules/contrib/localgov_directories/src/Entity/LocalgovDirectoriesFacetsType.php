<?php

namespace Drupal\localgov_directories\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Directory Facets type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "localgov_directories_facets_type",
 *   label = @Translation("Directory Facets type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\localgov_directories\Form\LocalgovDirectoriesFacetsTypeForm",
 *       "edit" = "Drupal\localgov_directories\Form\LocalgovDirectoriesFacetsTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\localgov_directories\LocalgovDirectoriesFacetsTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer directory facets types",
 *   bundle_of = "localgov_directories_facets",
 *   config_prefix = "localgov_directories_facets_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/localgov_directories_facets_types/add",
 *     "edit-form" = "/admin/structure/localgov_directories_facets_types/manage/{localgov_directories_facets_type}",
 *     "delete-form" = "/admin/structure/localgov_directories_facets_types/manage/{localgov_directories_facets_type}/delete",
 *     "collection" = "/admin/structure/localgov_directories_facets_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "weight",
 *   }
 * )
 */
class LocalgovDirectoriesFacetsType extends ConfigEntityBundleBase {

  /**
   * The machine name of this directory facets type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the directory facets type.
   *
   * @var string
   */
  protected $label;

  /**
   * Facet type weight.
   *
   * @var int
   */
  protected $weight = 0;

}
