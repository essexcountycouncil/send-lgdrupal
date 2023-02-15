<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Str Len plugin.
 *
 * @Tamper(
 *   id = "str_len",
 *   label = @Translation("Get string length"),
 *   description = @Translation("Get the length of a string"),
 *   category = "Text"
 * )
 */
class StrLen extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    return mb_strlen($data);
  }

}
