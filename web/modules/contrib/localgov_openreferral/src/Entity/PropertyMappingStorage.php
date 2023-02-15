<?php

namespace Drupal\localgov_openreferral\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines the storage class for property mapping configuration entities.
 */
class PropertyMappingStorage extends ConfigEntityStorage {

  /**
   * Load by Drupal Entity Type ID and Bundle.
   *
   * @param string $entity_type_id
   *   Entity Type ID.
   * @param string $bundle
   *   Bundle machine name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function loadByIds($entity_type_id, $bundle) {
    return $this->load($entity_type_id . '.' . $bundle);
  }

  /**
   * Load by Open Referral Type.
   *
   * @param string $public_type
   *   Open Referral class name.
   * @param string $public_datatype
   *   (Optional) Used for Taxonomy 'CURIE'.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects indexed by their ids.
   */
  public function loadByOpenreferralType($public_type, $public_datatype = NULL) {
    $properties = ['public_type' => $public_type];
    if (!is_null($public_datatype)) {
      $properties['public_datatype'] = $public_datatype;
    }
    return $this->storage->loadByProperties($properties);
  }

}
