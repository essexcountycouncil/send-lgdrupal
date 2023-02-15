<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_directories\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\search_api\Entity\Index as SearchIndex;

/**
 * Tests that indexing has been setup on the Facet selection field.
 *
 * @group localgov_directories
 */
class FacetIndexFieldSetupTest extends KernelTestBase {

  use EntityReferenceTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'block',
    'facets',
    'field',
    'image',
    'link',
    'node',
    'media',
    'search_api',
    'search_api_db',
    'system',
    'telephone',
    'text',
    'user',
    'views',
    'localgov_directories',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('search_api_task');
    $this->installConfig([
      'node',
      'search_api',
      'localgov_directories',
    ]);
  }

  /**
   * Test the existence of the Facet index field.
   *
   * The Search api index field for the Facet field is added when the
   * localgov_directory_facets_select field is added to a content type for the
   * first time.  This content type must be already part of the search index.
   */
  public function testFacetIndexFieldCreation() {

    $search_index = SearchIndex::load('localgov_directories_index_default');
    $facet_index_field = $search_index->getField('localgov_directory_facets_filter');
    $this->assertNull($facet_index_field);

    $dir_entry_content_type = strtolower($this->randomMachineName());
    NodeType::create(['type' => $dir_entry_content_type])->save();

    // Add content type to Search index.
    $this->createEntityReferenceField('node', $dir_entry_content_type, 'localgov_directory_channels', $this->randomString(), 'node');
    // Setup indexing on the Facet field.
    $this->createEntityReferenceField('node', $dir_entry_content_type, 'localgov_directory_facets_select', $this->randomString(), 'localgov_directories_facets');

    $search_index = SearchIndex::load('localgov_directories_index_default');
    $new_facet_index_field = $search_index->getField('localgov_directory_facets_filter');
    $this->assertNotNull($new_facet_index_field);
  }

}
