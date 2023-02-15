<?php

namespace Drupal\Tests\localgov_search_db\Kernel;

use Drupal\search_api\Entity\Index;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel test enabling server.
 *
 * @group localgov_search
 */
class InstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'node',
    'search_api',
    'search_api_db',
    'localgov_search',
    'views',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('search_api_index');
    $this->installEntitySchema('search_api_server');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('node');
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'localgov_search',
      'search_api_db',
      'search_api',
    ]);
  }

  /**
   * Test enabling/uninstall.
   */
  public function testEnableModule() {
    $index = Index::load('localgov_sitewide_search');
    $this->assertEmpty($index->getServerId());
    $this->assertFalse($index->status());
    \Drupal::service('module_installer')->install(['localgov_search_db']);
    $index = Index::load('localgov_sitewide_search');
    $this->assertEquals('localgov_sitewide_search', $index->getServerId());
    $this->assertTrue($index->status());
    \Drupal::service('module_installer')->uninstall(['localgov_search_db']);
    $index = Index::load('localgov_sitewide_search');
    $this->assertEquals('', $index->getServerId());
    $this->assertFalse($index->status());
  }

}
