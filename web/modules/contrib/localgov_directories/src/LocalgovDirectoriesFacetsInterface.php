<?php

namespace Drupal\localgov_directories;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a directory facets entity type.
 */
interface LocalgovDirectoriesFacetsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the directory facets title.
   *
   * @return string
   *   Title of the directory facets.
   */
  public function getTitle();

  /**
   * Sets the directory facets title.
   *
   * @param string $title
   *   The directory facets title.
   *
   * @return \Drupal\localgov_directories\LocalgovDirectoriesFacetsInterface
   *   The called directory facets entity.
   */
  public function setTitle($title);

  /**
   * Gets the directory facets creation timestamp.
   *
   * @return int
   *   Creation timestamp of the directory facets.
   */
  public function getCreatedTime();

  /**
   * Sets the directory facets creation timestamp.
   *
   * @param int $timestamp
   *   The directory facets creation timestamp.
   *
   * @return \Drupal\localgov_directories\LocalgovDirectoriesFacetsInterface
   *   The called directory facets entity.
   */
  public function setCreatedTime($timestamp);

}
