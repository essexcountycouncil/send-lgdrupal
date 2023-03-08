<?php

namespace Drupal\Tests\search_api_exclude_entity\Unit\Processor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_exclude_entity\Plugin\search_api\processor\SearchApiExcludeEntityProcessor;
use Drupal\Tests\search_api\Unit\Processor\TestItemsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Search API Exclude Entity" processor.
 *
 * @group search_api_exclude_entity
 *
 * @coversDefaultClass \Drupal\search_api_exclude_entity\Plugin\search_api\processor\SearchApiExcludeEntityProcessor
 */
class SearchApiExcludeEntityProcessorTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api_exclude_entity\Plugin\search_api\processor\SearchApiExcludeEntityProcessor
   */
  protected $processor;

  /**
   * The fields helper service.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldsHelper;

  /**
   * The test index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The field name of the first exclude field.
   *
   * @var string
   */
  protected $fieldName1 = 'field_exclude_1';

  /**
   * The field name of the second exclude field.
   *
   * @var string
   */
  protected $fieldName2 = 'field_exclude_2';

  /**
   * The value of an enabled (checked) exclude field.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $excludeEnabledValue;

  /**
   * The value of a disabled (unchecked) exclude field.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $excludeDisabledValue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpMockContainer();

    $this->fieldsHelper = \Drupal::getContainer()->get('search_api.fields_helper');
    $this->index = $this->createMock(IndexInterface::class);

    $this->excludeEnabledValue = $this->createMock(FieldItemListInterface::class);
    $this->excludeEnabledValue->method('getValue')
      ->willReturn([0 => ['value' => '1']]);
    $this->excludeDisabledValue = $this->createMock(FieldItemListInterface::class);
    $this->excludeDisabledValue->method('getValue')
      ->willReturn([0 => ['value' => '0']]);

    // Node type 'article' has two exclude fields.
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldMapByFieldType')
      ->with('search_api_exclude_entity')
      ->willReturn([
        'node' => [
          $this->fieldName1 => [
            'type' => 'search_api_exclude_entity',
            'bundles' => ['article' => 'article'],
          ],
          $this->fieldName2 => [
            'type' => 'search_api_exclude_entity',
            'bundles' => ['article' => 'article'],
          ],
        ],
      ]);

    $configuration['fields']['node'] = [
      $this->fieldName1,
      $this->fieldName2,
    ];

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $this->processor = new SearchApiExcludeEntityProcessor($configuration, 'search_api_exclude_entity_processor', []);
    $this->processor->setEntityFieldManager($entity_field_manager);
  }

  /**
   * Helper function for creating node based mock search index items.
   *
   * @param string $bundle
   *   The bundle name of the mock node.
   * @param string $raw_id
   *   The raw ID of the mock search index item.
   * @param \Drupal\Core\Field\FieldItemListInterface $field_value_1
   *   (Optional) The value of the first exclude field of the mock node.
   * @param \Drupal\Core\Field\FieldItemListInterface $field_value_2
   *   (Optional) The value of the second exclude field of the mock node.
   *
   * @return \Drupal\search_api\Item\ItemInterface
   *   The mock search index item.
   */
  protected function createMockItem($bundle, $raw_id, FieldItemListInterface $field_value_1 = NULL, FieldItemListInterface $field_value_2 = NULL) {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getEntityTypeId')->willReturn('node');
    $node->method('bundle')->willReturn($bundle);

    $node->method('get')
      ->will($this->returnValueMap([
        [$this->fieldName1, $field_value_1],
        [$this->fieldName2, $field_value_2],
      ]));

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    /** @var \Drupal\node\NodeInterface $node */
    $id = Utility::createCombinedId('entity:node', $raw_id);
    $item = $this->fieldsHelper->createItem($this->index, $id);
    $item->setOriginalObject(EntityAdapter::createFromEntity($node));

    return $item;
  }

  /**
   * Tests altering the indexed items.
   *
   * @covers ::alterIndexedItems
   */
  public function testAlterIndexedItems() {
    $item1 = $this->createMockItem(
      'page',
      '1:en'
    );
    $item2 = $this->createMockItem(
      'article',
      '2:en',
      $this->excludeDisabledValue,
      $this->excludeDisabledValue
    );
    $item3 = $this->createMockItem(
      'article',
      '3:en',
      $this->excludeDisabledValue,
      $this->excludeEnabledValue
    );
    $item4 = $this->createMockItem(
      'article',
      '4:en',
      $this->excludeEnabledValue,
      $this->excludeEnabledValue
    );

    $items = [
      $item1->getId() => $item1,
      $item2->getId() => $item2,
      $item3->getId() => $item3,
      $item4->getId() => $item4,
    ];

    $this->processor->alterIndexedItems($items);

    $this->assertArrayHasKey($item1->getId(), $items, "Item without any exclude fields wasn't removed.");
    $this->assertArrayHasKey($item2->getId(), $items, "Item with 0/2 of exclude fields enabled wasn't removed.");
    $this->assertArrayNotHasKey($item3->getId(), $items, "Item with 1/2 of exclude fields enabled was removed.");
    $this->assertArrayNotHasKey($item4->getId(), $items, "Item with 2/2 of exclude fields enabled was removed.");
  }

}
