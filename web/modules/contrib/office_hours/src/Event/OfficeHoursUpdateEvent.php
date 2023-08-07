<?php

namespace Drupal\office_hours\Event;

use Drupal\Component\EventDispatcher\Event;

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
   *   The Event values.
   */
  protected $values;

  /**
   * Constructs an values object.
   *
   * @param mixed $values
   *   The Event values.
   */
  public function __construct($values) {
    $this->values = $values;
  }

  /**
   * Returns event values.
   *
   * @return mixed
   *   The Event values.
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * Set event values.
   *
   * @param mixed $values
   *   The Event values.
   */
  public function setValues($values): void {
    $this->values = $values;
  }

}
