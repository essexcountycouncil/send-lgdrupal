<?php

namespace Drupal\field_formatter_class\Plugin\migrate\destination;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\PerComponentEntityDisplay;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

/**
 * Persist field formatter class data to the config system.
 *
 * @MigrateDestination(
 *   id = "field_formatter_class_settings"
 * )
 */
class FieldFormatterClassSettings extends PerComponentEntityDisplay {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = [];
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }
    $entity = $this->getEntity($values['entity_type'], $values['bundle'], $values[static::MODE_NAME]);
    $component = $entity->getComponent($values['field_name']);
    if ($component === NULL) {
      throw new MigrateException(
        sprintf('Cannot save field formatter class settings for a hidden field %s used in %s %s view mode %s', $values['field_name'], $values['entity_type'], $values['bundle'], $values[static::MODE_NAME]),
        0,
        NULL,
        MigrationInterface::MESSAGE_INFORMATIONAL,
        MigrateIdMapInterface::STATUS_IGNORED
      );
    }
    $component['third_party_settings']['field_formatter_class']['class'] =
      $row->getDestinationProperty('field_formatter_class_data');
    $entity->setComponent($values['field_name'], $component);
    $entity->save();
    return array_values($values);
  }

}
