<?php

namespace Drupal\Tests\localgov_directories_or\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests MappingInformation.
 *
 * @requires module localgov_openreferral
 * @group localgov_directories
 */
class FacetSyncTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'block',
    'facets',
    'field',
    'filter',
    'image',
    'link',
    'node',
    'media',
    'search_api',
    'search_api_db',
    'serialization',
    'system',
    'telephone',
    'text',
    'user',
    'views',
    'localgov_directories',
    'localgov_directories_or',
    'localgov_openreferral',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('search_api_task');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'node',
      'search_api',
      'localgov_directories',
    ]);
    $this->createContentType(['type' => 'localgov_directories_venue']);
  }

  /**
   * Test synchroniseFacetMappings.
   */
  public function testSyncroniseFacetMappings() {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $node_storage = $entity_type_manager->getStorage('node');
    $facet_storage = $entity_type_manager->getStorage('localgov_directories_facets_type');
    $mapping_storage = $entity_type_manager->getStorage('localgov_openreferral_mapping');

    $mapping_count = $mapping_storage->getQuery()->count()->accessCheck(FALSE)->execute();
    $facet_type = $facet_storage->create([
      'id' => 'facet_type_1',
      'label' => 'Facet Type 1',
    ]);
    $facet_type->save();
    $directory = $node_storage->create([
      'type' => 'localgov_directory',
      'title' => 'Directory 1',
    ]);
    $directory->save();

    $this->assertEquals($mapping_count, $mapping_storage->getQuery()->count()->accessCheck(FALSE)->execute(), 'No new mapping added');

    $directory->set('localgov_directory_facets_enable', [
      ['target_id' => 'facet_type_1'],
    ]);
    $directory->save();
    $this->assertEquals($mapping_count, $mapping_storage->getQuery()->count()->accessCheck(FALSE)->execute(), 'No new mapping added as directory not related to an OR entry type.');

    $directory->set('localgov_directory_channel_types', [
      ['target_id' => 'localgov_directories_venue'],
    ]);
    $directory->save();
    $this->assertEquals($mapping_count, $mapping_storage->getQuery()->count()->accessCheck(FALSE)->execute(), 'No new mapping added as directory not related to an OR entry type.');

    $mapping = $mapping_storage->create([
      'entity_type' => 'node',
      'bundle' => 'localgov_directories_venue',
      'public_type' => 'service',
    ]);
    $mapping->save();
    // Should add mapping itself, and one for the directory facet that is now
    // related to a directory with a open referral type.
    $mapping_count += 2;
    $this->assertEquals($mapping_count, $mapping_storage->getQuery()->count()->accessCheck(FALSE)->execute(), 'New mappings added.');
    $facet_mapping = $mapping_storage->load('localgov_directories_facets.facet_type_1');
    $this->assertEquals('taxonomy', $facet_mapping->getPublicType());

    $facet_type = $facet_storage->create([
      'id' => 'facet_type_2',
      'label' => 'Facet Type 2',
    ]);
    $facet_type->save();
    $directory->set('localgov_directory_facets_enable', [
      ['target_id' => 'facet_type_2'],
    ]);
    $directory->save();
    $this->assertEquals($mapping_count, $mapping_storage->getQuery()->count()->accessCheck(FALSE)->execute(), 'New mappings added, old removed.');
    $this->assertNull($mapping_storage->load('localgov_directories_facets.facet_type_1'));
    $facet_mapping = $mapping_storage->load('localgov_directories_facets.facet_type_2');
    $this->assertEquals('taxonomy', $facet_mapping->getPublicType());

    $directory->set('localgov_directory_facets_enable', []);
    $directory->save();
    $this->assertEquals(--$mapping_count, $mapping_storage->getQuery()->count()->accessCheck(FALSE)->execute(), 'Mappings removed.');

  }

}
