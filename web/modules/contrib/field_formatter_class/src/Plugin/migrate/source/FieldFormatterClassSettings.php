<?php

namespace Drupal\field_formatter_class\Plugin\migrate\source;

use Drupal\field\Plugin\migrate\source\d7\FieldInstancePerViewMode;

/**
 * Migration source plugin for field formatter class settings.
 *
 * @MigrateSource(
 *   id = "field_formatter_class_settings",
 *   source_module = "field_formatter_class"
 * )
 */
class FieldFormatterClassSettings extends FieldInstancePerViewMode {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $instances = parent::initializeIterator();
    $rows = [];
    foreach ($instances->getArrayCopy() as $instance) {
      // Returns only rows with field formatting classes.
      if (!empty($instance['formatter']['settings']['field_formatter_class'])) {
        $rows[] = $instance;
      }
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    return $this->initializeIterator()->count();
  }

}
