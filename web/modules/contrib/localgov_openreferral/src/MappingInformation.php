<?php

namespace Drupal\localgov_openreferral;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper service for querying details about Open Referral entity mappings.
 *
 * @todo this should now all be moved into the config entity.
 */
class MappingInformation {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mapping information storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Mapping information constrcutor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->storage = $this->entityTypeManager->getStorage('localgov_openreferral_mapping');
  }

  /**
   * Get Open Referral type for entity.
   *
   * @param string $entity_type
   *   Entity Type.
   * @param string $bundle
   *   Bundle.
   *
   * @return string
   *   Type, for example 'organization' or 'service'.
   */
  public function getPublicType($entity_type, $bundle) {
    $type = 'unknown';
    if ($mapping = $this->storage->load($entity_type . '.' . $bundle)) {
      $type = $mapping->getPublicType();
    }

    return $type;
  }

  /**
   * Get Open Referral data type for entity.
   *
   * @param string $entity_type
   *   Entity Type.
   * @param string $bundle
   *   Bundle.
   *
   * @return string
   *   Type, for example 'openActiveActivity'.
   */
  public function getPublicDataType($entity_type, $bundle) {
    $data_type = NULL;
    if ($mapping = $this->storage->load($entity_type . '.' . $bundle)) {
      $data_type = $mapping->getPublicDataType();
    }

    return $data_type;
  }

  /**
   * Get internal types by Open Referral type.
   */
  public function getInternalTypes($type, $data_type = '') {
    $properties = ['public_type' => $type];
    if ($data_type) {
      $properties['public_datatype'] = $data_type;
    }
    $mappings = $this->storage->loadByProperties($properties);
    $internal_types = [];
    foreach ($mappings as $map) {
      $internal_types[] = [
        'entity_type' => $map->mappedEntityType(),
        'bundle' => $map->mappedBundle(),
      ];
    }
    return $internal_types;
  }

  /**
   * Get property mapping.
   *
   * @param string $entity_type
   *   Entity Type ID.
   * @param string $bundle
   *   Entity type bundle machine name.
   * @param string $context
   *   Either `denormalize`, or when normalizing: the direct parent open
   *   referral entity or `__root`.
   *
   * @return array
   *   Mapped properties, or empty if none.
   */
  public function getPropertyMapping($entity_type, $bundle, $context) {
    $mapping = $this->storage->load($entity_type . '.' . $bundle);
    if (empty($mapping)) {
      return [];
    }

    return $mapping->getMapping($context);
  }

}
