<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_directories\Kernel;

use Drupal\Tests\search_api\Kernel\ResultsTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Utility\Utility;

/**
 * Tests population of the search sort field.
 *
 * @group localgov_directories
 */
class IndexTitleSortTest extends KernelTestBase {

  use EntityReferenceTestTrait;
  use NodeCreationTrait;
  use ResultsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'block',
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
   * The directory search index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The Entity Type indexed as items.
   *
   * @var Drupal\Core\Entity\EntityTypeInterface
   */
  protected $itemType;

  /**
   * The nodes created for testing.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_task');
    $this->installConfig([
      'node',
      'search_api',
      'localgov_directories',
    ]);
    // Enable localgov_directories_db such that all hooks called.
    $this->container->get('module_installer')->install(['localgov_directories_db'], FALSE);

    // @todo check if we need this in the end.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    $item_type_name = strtolower($this->randomMachineName());
    $this->itemType = NodeType::create(['type' => $item_type_name]);
    $this->itemType->save();
    // Add the sort field.
    FieldConfig::create([
      'field_name' => 'localgov_directory_title_sort',
      'entity_type' => 'node',
      'bundle' => $item_type_name,
      'label' => $this->randomString(),
    ])->save();
    // Add content type to search index.
    $this->createEntityReferenceField('node', $item_type_name, 'localgov_directory_channels', $this->randomString(), 'node');

    $this->index = Index::load('localgov_directories_index_default');
    // We're not testing rendered item, and it requires pulling in a whole load
    // of dependencies.
    $this->index->removeField('rendered_item');
    $this->index->save();
  }

  /**
   * Test the value of the sort field.
   */
  public function testItemsSortValue() {
    $this->nodes[1] = Node::create([
      'type' => $this->itemType->id(),
      'title' => 'abc',
      'status' => 1,
    ]);
    $this->nodes[1]->save();

    $this->nodes[2] = Node::create([
      'type' => $this->itemType->id(),
      'title' => 'cde',
      'localgov_directory_title_sort' => ['value' => 'fgh'],
      'status' => 1,
    ]);
    $this->nodes[2]->save();

    $this->index->reindex();
    $indexed = $this->index->indexItems();
    $this->assertEquals(2, $indexed);

    $query = new Query($this->index);
    $query->addCondition('localgov_directory_title_sort', 'fgh');
    $result = $query->execute();
    $expected = ['node' => [2]];
    $this->assertResults($result, $expected);

    $query = new Query($this->index);
    $query->addCondition('localgov_directory_title_sort', 'abc');
    $result = $query->execute();
    $expected = ['node' => [1]];
    $this->assertResults($result, $expected);
  }

}
