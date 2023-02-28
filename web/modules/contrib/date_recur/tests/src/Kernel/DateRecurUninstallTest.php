<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests uninstall.
 *
 * @group date_recur
 */
final class DateRecurUninstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_entity_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * Tests uninstall.
   */
  public function testUninstall() {
    $database = \Drupal::database();
    $this->installEntitySchema('dr_entity_test_rev');
    $this->installSchema('user', ['users_data']);

    $tableName = 'date_recur__dr_entity_test_rev__abc123';

    $this->assertFalse($database->schema()->tableExists($tableName));

    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => 'dr_entity_test_rev',
      'field_name' => 'abc123',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $fieldStorage->save();

    $this->assertTrue($database->schema()->tableExists($tableName));

    $fieldStorage->delete();

    $this->assertFalse($database->schema()->tableExists($tableName));

    /** @var \Drupal\Core\CronInterface $cron */
    $cron = \Drupal::service('cron');
    $cron->run();

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = \Drupal::service('module_installer');
    $moduleInstaller->uninstall(['date_recur']);
  }

}
