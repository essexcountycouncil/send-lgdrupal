<?php

namespace Drupal\Tests\localgov_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\search_api\Entity\Index;
use Drupal\views\Entity\View;

/**
 * Check that content types are added to the sitewide search.
 *
 * @group localgov_search
 */
class ContentTypesAddedToSearchTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'text',
    'node',
    'search_api',
    'search_api_db',
    'system',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('node');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'node',
      'search_api',
      'search_api_db',
      'system',
    ]);
  }

  /**
   * Test content types added to search view and index.
   */
  public function testSearchViewAndIndexSettings() {

    // Test content type added to search view and index when enabling module.
    $this->createContentType([
      'type' => 'test1',
      'name' => 'Test node type 1',
    ]);
    \Drupal::service('module_installer')->install(['localgov_search']);
    $view = View::load('localgov_sitewide_search');
    $display = $view->get('display');
    $this->assertEquals('search_result', $display['default']['display_options']['row']['options']['view_modes']['entity:node']['test1']);
    $index = Index::load('localgov_sitewide_search');
    $field_config = $index->getField('rendered_item')->getConfiguration();
    $this->assertEquals('search_index', $field_config['view_mode']['entity:node']['test1']);

    // Test content type added to search view and index when creating type.
    $this->createContentType([
      'type' => 'test2',
      'name' => 'Test node type 2',
    ]);
    $view = View::load('localgov_sitewide_search');
    $display = $view->get('display');
    $this->assertEquals('search_result', $display['default']['display_options']['row']['options']['view_modes']['entity:node']['test2']);
    $index = Index::load('localgov_sitewide_search');
    $field_config = $index->getField('rendered_item')->getConfiguration();
    $this->assertEquals('search_index', $field_config['view_mode']['entity:node']['test2']);
  }

}
