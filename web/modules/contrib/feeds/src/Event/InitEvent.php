<?php

namespace Drupal\feeds\Event;

use Drupal\feeds\FeedInterface;

/**
 * Fired before a regular event is being fired.
 *
 * This allows listeners to lazily set themselves up.
 */
class InitEvent extends EventBase {

  /**
   * The stage to initialize.
   *
   * @var string
   */
  protected $stage;

  /**
   * Constructs an InitEvent object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param string $stage
   *   (optional) The stage to initialize. Defaults to an empty string.
   */
  public function __construct(FeedInterface $feed, $stage = '') {
    $this->feed = $feed;
    $this->stage = $stage;
  }

  /**
   * Returns the stage to initialize.
   *
   * @return string
   *   The stage to initialize.
   */
  public function getStage() {
    return $this->stage;
  }

}
