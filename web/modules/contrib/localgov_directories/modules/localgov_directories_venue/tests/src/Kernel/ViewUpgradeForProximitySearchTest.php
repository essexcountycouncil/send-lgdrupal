<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_directories_venue\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_directories\Constants as Directory;
use Drupal\search_api\Entity\Index as SearchIndex;
use Drupal\views\Views;

/**
 * Tests additions to the localgov_directory_channel_view.
 *
 * The proximity search support is added to the localgov_directory_channel_view
 * when any new content type with the localgov_location field (e.g.
 * localgov_directories_venue) is installed.
 */
class ViewUpgradeForProximitySearchTest extends KernelTestBase {

  /**
   * Tests directory channel view modification.
   *
   * When the localgov_directories_venue module is installed:
   * - A new display is added to the view to support proximity search.
   * - A new facet that uses this new display as a facet source is also added.
   * Here we verify the existence of the new display and the facet.
   */
  public function testViewUpgrade(): void {

    $has_location_search = SearchIndex::load(Directory::DEFAULT_INDEX)
      ->getServerInstance()
      ->supportsDataType(Directory::SEARCH_API_LOCATION_DATATYPE);
    if (!$has_location_search) {
      $this->markTestSkipped('Database lacks location search feature.');
      return;
    }

    $view = Views::getView(Directory::CHANNEL_VIEW);
    $view->setDisplay(Directory::CHANNEL_VIEW_PROXIMITY_SEARCH_DISPLAY);
    $display_for_proximity_search = $view->getDisplay();
    $has_proximity_search_display = !empty($display_for_proximity_search);
    $this->assertTrue($has_proximity_search_display);

    $filters = $display_for_proximity_search->getOption('filters');
    $has_location_filter = array_key_exists('localgov_location', $filters);
    $this->assertTrue($has_location_filter);

    $facet_for_proximity_search = \Drupal::service('entity_type.manager')->getStorage('facets_facet')->load('localgov_directories_facets_proximity_search');
    $has_facet_for_proximity_search = !empty($facet_for_proximity_search);
    $this->assertTrue($has_facet_for_proximity_search);
  }

  /**
   * Sets up location search for venues.
   *
   * - Activates the Directory search index.
   * - Enables localgov_directories_venue module.
   * - Adds the localgov_location field to the search index.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);

    $this->installEntitySchema('node');
    $this->installEntitySchema('search_api_task');

    $this->installConfig([
      'node',
      'search_api',
      'localgov_directories',
      'localgov_directories_db',
    ]);

    // Enable the Directory search index.
    $localgov_directories_db_module_path = $this->container->get('extension.list.module')->getPath('localgov_directories_db');
    require_once "$localgov_directories_db_module_path/localgov_directories_db.install";
    localgov_directories_db_install(FALSE);

    // Now that the search index is available, we are ready to add the
    // localgov_location field to this index.  So let's import it.
    // @see localgov_directories_location_field_config_insert().
    $this->installConfig([
      'localgov_directories_location',
      'localgov_directories_venue',
    ]);
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'address',
    'block',
    'entity_browser',
    'facets',
    'field',
    'field_group',
    'geofield',
    'image',
    'leaflet',
    'leaflet_views',
    'link',
    'localgov_directories',
    'localgov_directories_db',
    'localgov_directories_location',
    'localgov_directories_venue',
    'localgov_geo',
    'media',
    'media_library',
    'node',
    'path_alias',
    'pathauto',
    'search_api',
    'search_api_db',
    'search_api_location_views',
    'system',
    'telephone',
    'text',
    'token',
    'user',
    'views',
  ];

}
