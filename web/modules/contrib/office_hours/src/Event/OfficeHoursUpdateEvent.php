<?php

namespace Drupal\office_hours\Event;

// use Symfony\Component\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines an event.
 *
 * @package Drupal\office_hours\Event
 */
class OfficeHoursUpdateEvent extends Event {

  /**
   * Field values array.
   *
   * @var array
   */
  protected $values;

  /**
   * @param mixed $values
   */
  public function __construct($values) {
    $this->values = $values;
  }

  /**
   * @return mixed
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * @param mixed $values
   */
  public function setValues($values): void {
    $this->values = $values;
  }

}
