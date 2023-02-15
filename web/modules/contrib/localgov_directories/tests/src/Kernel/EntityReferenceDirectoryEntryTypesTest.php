<?php

namespace Drupal\Tests\localgov_directories\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests channels entity reference selection handler.
 *
 * @group localgov_directories
 */
class EntityReferenceDirectoryEntryTypesTest extends KernelTestBase {

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
    'filter',
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
   * Nodes for testing.
   *
   * @var string[][]
   */
  protected $nodes = [];

  /**
   * The selection handler.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'facets',
      'filter',
      'node',
      'search_api',
      'localgov_directories',
    ]);
  }

  /**
   * Tests the selection handler.
   */
  public function testSelectionHandler() {
    $entry_types = [];
    // Two content types implementing the magically named
    // 'localgov_directory_channels' field.
    $page_type = strtolower($this->randomMachineName());
    $entry_types[$page_type] = NodeType::create([
      'type' => $page_type,
      'name' => $this->randomMachineName(),
    ]);
    $entry_types[$page_type]->save();
    $handler_settings = [
      'sort' => [
        'field' => 'title',
        'direction' => 'DESC',
      ],
    ];
    $this->createEntityReferenceField('node', $page_type, 'localgov_directory_channels', $this->randomString(), 'node', 'localgov_directories_channels_selection', $handler_settings);

    $other_type = strtolower($this->randomMachineName());
    $entry_types[$other_type] = NodeType::create([
      'type' => $other_type,
      'name' => $this->randomMachineName(),
    ]);
    $entry_types[$other_type]->save();
    $handler_settings = [
      'sort' => [
        'field' => 'title',
        'direction' => 'DESC',
      ],
    ];
    $this->createEntityReferenceField('node', $other_type, 'localgov_directory_channels', $this->randomString(), 'node', 'localgov_directories_channels_selection', $handler_settings);

    // Check the nodes with the reference field are returned.
    $field_config = FieldConfig::loadByName('node', 'localgov_directory', 'localgov_directory_channel_types');
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config);
    $selection = $this->selectionHandler->getReferenceableEntities();
    foreach ($selection['node_type'] as $machine_name => $label) {
      $this->assertSame($label, $entry_types[$machine_name]->label());
    }

    // Check a non-directory entry is not returned.
    $non_directory = NodeType::create([
      'type' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ]);
    $non_directory->save();
    foreach ($selection['node_type'] as $machine_name => $label) {
      $this->assertSame($label, $entry_types[$machine_name]->label());
    }

    // Check count.
    $this->assertEquals(1, $this->selectionHandler->countReferenceableEntities());

    // Check validate.
    $this->assertGreaterThan(0, count($this->selectionHandler->validateReferenceableEntities([$other_type])));
    $this->assertGreaterThan(0, count($this->selectionHandler->validateReferenceableEntities([
      $page_type,
      $other_type,
    ])));
    $this->assertEquals(0, count($this->selectionHandler->validateReferenceableEntities([$non_directory->id()])));
  }

}
