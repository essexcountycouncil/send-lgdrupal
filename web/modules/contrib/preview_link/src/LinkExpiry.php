<?php

namespace Drupal\preview_link;

use Drupal\Core\State\StateInterface;

/**
 * Calculates link expiry time.
 */
class LinkExpiry {

  /**
   * Default expiry time in days.
   *
   * @var int
   */
  const DEFAULT_EXPIRY_DAYS = 7;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * LinkExpiry constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Calculates default lifetime of a preview link.
   *
   * @return int
   *   Preview link in seconds.
   */
  public function getLifetime() {
    $days = $this->state->get('preview_link_expiry_days', static::DEFAULT_EXPIRY_DAYS);
    return $days * 86400;
  }

}
