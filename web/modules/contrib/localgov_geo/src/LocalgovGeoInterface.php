<?php

namespace Drupal\localgov_geo;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a geo entity type.
 */
interface LocalgovGeoInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the geo creation timestamp.
   *
   * @return int
   *   Creation timestamp of the geo.
   */
  public function getCreatedTime();

  /**
   * Sets the geo creation timestamp.
   *
   * @param int $timestamp
   *   The geo creation timestamp.
   *
   * @return \Drupal\localgov_geo\LocalgovGeoInterface
   *   The called geo entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the geo status.
   *
   * @return bool
   *   TRUE if the geo is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the geo status.
   *
   * @param bool $status
   *   TRUE to enable this geo, FALSE to disable.
   *
   * @return \Drupal\localgov_geo\LocalgovGeoInterface
   *   The called geo entity.
   */
  public function setStatus($status);

}
