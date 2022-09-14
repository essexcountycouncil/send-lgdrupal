<?php

namespace Drupal\send_directory_import\Plugin\Migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Source plugin for Directories in csv.
 *
 * @MigrateSource(
 *   id = "send_directory_csv"
 * )
 */
class directoryCSV extends CSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Delta is here the row number.
    $delta = $this->file->key();
    $row->setSourceProperty('id', $delta);
    return parent::prepareRow($row);
  }

}
