<?php

namespace Drupal\Tests\dbal\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests dbal_connection service.
 *
 * @group dbal
 */
class DbalConnectionKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'dbal'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // We use the semaphore table, which is created by acquiring a lock.
    $this->container->get('lock.persistent')->acquire('dbal_test');
    $this->container->get('lock.persistent')->release('dbal_test');
  }

  /**
   * Tests dbal_connection service and factory.
   */
  public function testConnectionFactory() {
    $database = $this->container->get('database');
    $connection = $this->container->get('dbal_connection');
    $connection->insert($database->getFullQualifiedTableName('semaphore'),
      [
        'name' => 'dbal_test',
        'value' => 'dbal_test',
        'expire' => time(),
      ]);
    $this->assertEquals('dbal_test', $database->select('semaphore', 's')
      ->condition('name', 'dbal_test')
      ->fields('s', ['value'])
      ->execute()
      ->fetchField());
  }

}
