<?php

namespace Drupal\preview_link_test;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;

/**
 * Service used to simulate time.
 */
class TimeMachine implements TimeInterface {

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * TimeMachine constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->getTime()->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestMicroTime() {
    return (float) $this->getTime()->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    return $this->getTime()->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentMicroTime() {
    return (float) $this->getTime()->getTimestamp();
  }

  /**
   * Sets time.
   *
   * @param \DateTimeInterface $dateTime
   *   Sets the time.
   */
  public function setTime(\DateTimeInterface $dateTime) {
    if ($dateTime instanceof \DateTime) {
      $dateTime = \DateTimeImmutable::createFromMutable($dateTime);
    }
    $this->state->set('preview_link_test_time_machine', $dateTime);
  }

  /**
   * Get the time from state.
   *
   * @returns \DateTimeImmutable
   *   The date time.
   *
   * @throws \LogicException
   *   When date time was not set.
   */
  protected function getTime() {
    $dateTime = $this->state->get('preview_link_test_time_machine');
    if (!isset($dateTime)) {
      throw new \LogicException('Current date time not set.');
    }
    return $dateTime;
  }

}
