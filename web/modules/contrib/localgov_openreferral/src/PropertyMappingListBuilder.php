<?php

namespace Drupal\localgov_openreferral;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\localgov_openreferral\Entity\PropertyMappingInterface;

/**
 * Provides a listing of property mappings.
 */
class PropertyMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['entity_type'] = $this->t('Mapped entity type');
    $header['bundle'] = $this->t('Mapped bundle');
    $header['public_datatype'] = $this->t('Open Referral type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    assert($entity instanceof PropertyMappingInterface);
    $row['id'] = $entity->id();
    $row['entity_type'] = $entity->mappedEntityType();
    $row['bundle'] = $entity->mappedBundle();
    $row['public_datatype'] = $entity->getPublicType();
    return $row + parent::buildRow($entity);
  }

}
