<?php

namespace Drupal\tamper;

use Drupal\tamper\TamperableItemInterface;

/**
 * Defines a single tamper item class.
 */
class TamperItem implements TamperableItemInterface {

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return get_object_vars($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceProperty($property) {
    return isset($this->$property) ? $this->$property : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceProperty($property, $data) {
    $this->$property = $data;
    return $this;
  }

}
