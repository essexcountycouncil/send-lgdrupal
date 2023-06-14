<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for skipping applying further Tamper plugins.
 *
 * @Tamper(
 *   id = "skip_on_empty",
 *   label = @Translation("Skip tampers on empty"),
 *   description = @Translation("If it is empty, further Tamper plugins won't be applied."),
 *   category = "Filter"
 * )
 */
class SkipOnEmpty extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (empty($data)) {
      throw new SkipTamperDataException('Item is empty.');
    }

    return $data;
  }

}
