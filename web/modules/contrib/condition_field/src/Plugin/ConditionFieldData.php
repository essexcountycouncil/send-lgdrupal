<?php

namespace Drupal\condition_field\Plugin;

use Drupal\Core\TypedData\TypedData;

/**
 * Class of ConditionFieldData.
 *
 * @package Drupal\condition_field
 */
class ConditionFieldData extends TypedData {
  /**
   * {@inheritdoc}
   */
  protected $value;

  /**
   * Summary of getValue.
   *
   * @param mixed $langcode
   *   The langcode.
   *
   * @return mixed
   *   It will return mixed.
   */
  public function getValue($langcode = NULL) {
    return $this->value;
  }

}
