<?php

namespace Drupal\localgov_openreferral\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the property mapping entity type.
 *
 * @ConfigEntityType(
 *   id = "localgov_openreferral_mapping",
 *   label = @Translation("Property Mapping"),
 *   label_collection = @Translation("Property Mappings"),
 *   label_singular = @Translation("property mapping"),
 *   label_plural = @Translation("property mappings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count property mapping",
 *     plural = "@count property mappings",
 *   ),
 *   config_prefix = "property_mapping",
 *   admin_permission = "administer localgov_openreferral_mapping",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "entity_type",
 *     "bundle",
 *     "public_type",
 *     "public_datatype",
 *     "property_mappings"
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\localgov_openreferral\PropertyMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\localgov_openreferral\Form\PropertyMappingForm",
 *       "edit" = "Drupal\localgov_openreferral\Form\PropertyMappingForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "storage" = "Drupal\localgov_openreferral\Entity\PropertyMappingStorage"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/openreferral",
 *     "add-form" = "/admin/config/services/openreferral/add",
 *     "edit-form" = "/admin/config/services/openreferral/{localgov_openreferral_mapping}",
 *     "delete-form" = "/admin/config/services/openreferral/{localgov_openreferral_mapping}/delete"
 *   }
 * )
 */
class PropertyMapping extends ConfigEntityBase implements PropertyMappingInterface {

  /**
   * The property mapping ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type the mapping is for.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The bundle the mapping is for.
   *
   * __default for fallback for bundles.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Open Referral class type.
   *
   * @var string
   */
  protected $public_type;

  /**
   * Open Referral data type.
   *
   * Used for taxonomy 'curie'.
   *
   * @var string
   */
  protected $public_datatype = '';

  /**
   * Array of mappings.
   *
   * 'default' is the standard context.
   *
   * @var array
   */
  protected $property_mappings = [];

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return $this->entity_type . '.' . $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function mappedEntityType():? string {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function mappedBundle():? string {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicType(string $public_type) {
    $this->public_type = $public_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicType():? string {
    return $this->public_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicDataType(string $public_datatype) {
    $this->public_datatype = $public_datatype;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicDataType():? string {
    return $this->public_datatype;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapping(array $mapping, string $context = 'default') {
    $this->property_mappings[$context] = $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapping(string $context, bool $exact = FALSE): array {
    return $this->property_mappings[$context] ?? $this->property_mappings['default'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Create dependency for entity and bundle mapped.
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->mappedEntityType());
    $this->addDependency('module', $entity_type->getProvider());
    $bundle_config_dependency = $entity_type->getBundleConfigDependency($this->mappedBundle());
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    return $this;
  }

}
