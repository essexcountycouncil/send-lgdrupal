<?php

namespace Drupal\preview_link\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the preview link entity.
 */
interface PreviewLinkInterface extends ContentEntityInterface {

  /**
   * The URL for this preview link.
   *
   * @return \Drupal\Core\Url
   *   The url object.
   */
  public function getUrl();

  /**
   * Gets thew new token.
   *
   * @return string
   *   The token.
   */
  public function getToken();

  /**
   * Set the new token.
   *
   * @param string $token
   *   The new token.
   *
   * @return \Drupal\preview_link\Entity\PreviewLinkInterface
   *   Returns the preview link for chaining.
   */
  public function setToken($token);

  /**
   * Mark the entity needing a new token. Only updated upon save.
   *
   * @param bool $needs_new_token
   *   Tell this entity to generate a new token.
   *
   * @return bool
   *   TRUE if it was currently marked to generate otherwise FALSE.
   */
  public function regenerateToken($needs_new_token = FALSE);

  /**
   * Gets the timestamp stamp of when the token was generated.
   *
   * @return int
   *   The timestamp.
   */
  public function getGeneratedTimestamp();

}
