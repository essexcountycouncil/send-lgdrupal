<?php

namespace Drupal\localgov_directories;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of directory facets type entities.
 *
 * @see \Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType
 */
class LocalgovDirectoriesFacetsTypeListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['label'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No directory facets types available. <a href=":link">Add directory facets type</a>.',
      [':link' => Url::fromRoute('entity.localgov_directories_facets_type.add_form')->toString()]
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'localgov_directories_facet_type_list_form';
  }

}
