<?php

namespace Drupal\Tests\localgov_directories\Kernel;

use Drupal\Component\Utility\Html;
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
class EntityReferenceChannelsSelectionTest extends KernelTestBase {

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

    // Create test nodes.
    $node1 = $this->createNode(['type' => 'localgov_directory']);
    $node2 = $this->createNode(['type' => 'localgov_directory']);
    $node3 = $this->createNode(['type' => 'localgov_directory']);

    $this->directory_nodes = [];
    foreach ([$node1, $node2, $node3] as $node) {
      $this->directory_nodes[$node->id()] = $node;
    }

    $this->page_type = strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->page_type])->save();
    $handler_settings = [
      'sort' => [
        'field' => 'title',
        'direction' => 'DESC',
      ],
    ];
    $this->createEntityReferenceField('node', $this->page_type, 'localgov_directory_channels', $this->randomString(), 'node', 'localgov_directories_channels_selection', $handler_settings);

    $this->other_type = strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->other_type])->save();
    $handler_settings = [
      'sort' => [
        'field' => 'title',
        'direction' => 'DESC',
      ],
    ];
    $this->createEntityReferenceField('node', $this->other_type, 'localgov_directory_channels', $this->randomString(), 'node', 'localgov_directories_channels_selection', $handler_settings);
  }

  /**
   * Tests the selection handler.
   */
  public function testSelectionHandler() {
    // Check the three directory nodes are returned.
    $field_config = FieldConfig::loadByName('node', $this->page_type, 'localgov_directory_channels');
    $page = $this->createNode(['type' => $this->page_type]);
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config, $page);
    $selection = $this->selectionHandler->getReferenceableEntities();
    foreach ($selection as $node_type => $values) {
      foreach ($values as $nid => $label) {
        $this->assertSame($node_type, $this->directory_nodes[$nid]->bundle());
        $this->assertSame(trim(strip_tags($label)), Html::escape($this->directory_nodes[$nid]->label()));
      }
    }

    // Remove one directory node and make it only accessible to the other type.
    $directory = array_pop($this->directory_nodes);
    $directory->localgov_directory_channel_types = [['target_id' => $this->other_type]];
    $directory->save();
    $selection = $this->selectionHandler->getReferenceableEntities();
    foreach ($selection as $node_type => $values) {
      foreach ($values as $nid => $label) {
        $this->assertSame($node_type, $this->directory_nodes[$nid]->bundle());
        $this->assertSame(trim(strip_tags($label)), Html::escape($this->directory_nodes[$nid]->label()));
      }
    }

    // Check the removed node is accessible to the other type.
    $field_config = FieldConfig::loadByName('node', $this->other_type, 'localgov_directory_channels');
    $other = $this->createNode(['type' => $this->other_type]);
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config, $other);
    $other_selection = $this->selectionHandler->getReferenceableEntities();
    $this->directory_nodes[$directory->id()] = $directory;
    foreach ($other_selection as $node_type => $values) {
      foreach ($values as $nid => $label) {
        $this->assertSame($node_type, $this->directory_nodes[$nid]->bundle());
        $this->assertSame(trim(strip_tags($label)), Html::escape($this->directory_nodes[$nid]->label()));
      }
    }

  }

}
