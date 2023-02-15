<?php

namespace Drupal\localgov_openreferral\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a property mapping entity type.
 */
interface PropertyMappingInterface extends ConfigEntityInterface {

  /**
   * Get the mapped entity's type id.
   *
   * @return string|null
   *   Entity Type ID. Null if a new unset entity.
   */
  public function mappedEntityType():? string;

  /**
   * Get the mapped entity's bundle id.
   *
   * @return string|null
   *   Bundle ID. Null if a new unset entity.
   */
  public function mappedBundle():? string;

  /**
   * Set the Open Referral destination class type.
   *
   * For example 'service', 'organization'.
   *
   * @param string $public_type
   *   The Open Referral type.
   */
  public function setPublicType(string $public_type);

  /**
   * Get the Open Referral destination class type.
   *
   * @return string|null
   *   The Open Referral type. Null if a new unset entity.
   */
  public function getPublicType():? string;

  /**
   * Set the Open Referral destination class data type.
   *
   * Used for taxonomy identifers.
   * For example 'esdNeeds', 'openActiveActivity'.
   *
   * @param string $public_datatype
   *   The Open Referral data type.
   */
  public function setPublicDataType(string $public_datatype);

  /**
   * Get the Open Referral destination class data type.
   *
   * @return string|null
   *   The Open Referral data type if one set.
   */
  public function getPublicDataType():? string;

  /**
   * Set the Open Referral field property mapping.
   *
   * @param array $mapping
   *   Array of field_name, public_name mappings.
   * @param string $context
   *   Optional context.
   */
  public function setMapping(array $mapping, string $context = 'default');

  /**
   * Get the Open Referral field property mapping.
   *
   * @param string $context
   *   The mapping context.
   * @param bool $exact
   *   Optional. True will only return the context and not default.
   *
   * @return array
   *   The Open Referral mapping array.
   */
  public function getMapping(string $context, bool $exact = FALSE): array;

}
