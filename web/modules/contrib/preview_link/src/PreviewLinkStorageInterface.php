<?php

namespace Drupal\preview_link;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Interface for Preview Link entities.
 */
interface PreviewLinkStorageInterface extends SqlEntityStorageInterface {

  /**
   * Gets the preview link for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity we want the preview link for.
   *
   * @return \Drupal\preview_link\Entity\PreviewLinkInterface|false
   *   The preview link entity if it exists otherwise FALSE.
   */
  public function getPreviewLinkForEntity(ContentEntityInterface $entity);

  /**
   * Gets the preview link given entity type Id and entity Id.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\preview_link\Entity\PreviewLinkInterface|false
   *   The preview link entity if it exists otherwise FALSE.
   */
  public function getPreviewLink(ContentEntityInterface $entity);

  /**
   * Creates a new preview link from a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity the preview link is for.
   *
   * @return \Drupal\preview_link\Entity\PreviewLinkInterface
   *   The preview link entity.
   */
  public function createPreviewLinkForEntity(ContentEntityInterface $entity);

  /**
   * Creates a new preview link from a entity type Id and entity Id.
   *
   * @param string $entity_type_id
   *   The entity type Id.
   * @param string $entity_id
   *   The entity id.
   *
   * @return \Drupal\preview_link\Entity\PreviewLinkInterface
   *   The preview link entity.
   */
  public function createPreviewLink($entity_type_id, $entity_id);

}
