<?php

namespace Drupal\Tests\localgov_directories\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Site\Settings;

/**
 * Tests DirectoriesConfigSubscriber.
 *
 * @group localgov_directories
 */
class IgnoreTypeConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'localgov_directories',
    'localgov_directories_facets_ignore_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'localgov_directories_facets_ignore_test']);
  }

  /**
   * Test excluding modules from the config export.
   */
  public function testExcludedModules() {
    // Assert that our facet config is in the active config.
    $active = $this->container->get('config.storage');
    $this->assertNotEmpty($active->listAll('localgov_directories.localgov_directories_facets_type.'));
    $this->assertNotEmpty($active->listAll('system.'));
    // Add collections.
    $collection = $this->randomMachineName();
    foreach ($active->listAll() as $config) {
      $active->createCollection($collection)->write($config, $active->read($config));
    }

    // Assert that facet config is not in the export storage.
    $export = $this->container->get('config.storage.export');
    $this->assertEmpty($export->listAll('localgov_directories.localgov_directories_facets_type.'));
    $this->assertNotEmpty($export->listAll('system.'));
    // And assert excluded from collections too.
    $this->assertEmpty($export->createCollection($collection)->listAll('localgov_directories.localgov_directories_facets_type.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('system.'));

    // Assert that existing facet config is again in the import storage.
    $import = $this->container->get('config.import_transformer')->transform($export);
    $this->assertNotEmpty($import->listAll('localgov_directories.localgov_directories_facets_type.'));
    $this->assertNotEmpty($import->listAll('system.'));

    // Enable export.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['localgov_directories_stage_site'] = TRUE;
    new Settings($settings);
    drupal_flush_all_caches();
    // Assert that facet config is in the export storage.
    $export = $this->container->get('config.storage.export');
    $this->assertNotEmpty($export->listAll('localgov_directories.localgov_directories_facets_type.'));
    $this->assertNotEmpty($export->listAll('system.'));
    // And assert excluded from collections too.
    $this->assertNotEmpty($export->createCollection($collection)->listAll('localgov_directories.localgov_directories_facets_type.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('system.'));

    // Assert config not removed if it exists in exported storage.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['localgov_directories_stage_site'] = FALSE;
    new Settings($settings);
    drupal_flush_all_caches();
    $sync = $this->container->get('config.storage.sync');
    $active = $this->container->get('config.storage');
    // Store sync storage, and make changes to active storage.
    $this->copyConfig($active, $sync);
    $active_facet_type_config = $active->read('localgov_directories.localgov_directories_facets_type.test');
    $active_facet_type_config['label'] = 'Updated';
    $active->write('localgov_directories.localgov_directories_facets_type.test', $active_facet_type_config);
    $active_system_config = $active->read('system.site');
    $active_system_config['name'] = 'Updated';
    $active->write('system.site', $active_system_config);
    // Assert that facet config remains as is,
    // but update to sytem is exported.
    $export = $this->container->get('config.storage.export');
    $export_facet_type_config = $export->read('localgov_directories.localgov_directories_facets_type.test');
    $this->assertEquals($export_facet_type_config['label'], 'Test Facet Type');
    $export_system_config = $export->read('system.site');
    $this->assertEquals($export_system_config['name'], 'Updated');

    // Assert config is imported if present.
    $import = $this->container->get('config.import_transformer')->transform($export);
    $import_facet_type_config = $import->read('localgov_directories.localgov_directories_facets_type.test');
    $this->assertEquals($import_facet_type_config['label'], 'Test Facet Type');
  }

}
