<?php

namespace Drupal\condition_field\Plugin;

use Drupal\Core\TypedData\TypedData;

/**
 * Class ConditionFieldData.
 *
 * @package Drupal\condition_field
 */
class ConditionFieldData extends TypedData {

  protected $value;

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    return $this->value;
  }

}
