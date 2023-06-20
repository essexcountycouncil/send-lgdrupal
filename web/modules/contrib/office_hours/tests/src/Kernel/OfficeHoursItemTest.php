<?php

namespace Drupal\Tests\office_hours\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Class that tests OfficeHoursField.
 *
 * @package Drupal\Tests\office_hours\Kernel
 *
 * @group office_hours
 * @coversDefaultClass \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem
 */
class OfficeHoursItemTest extends FieldKernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['office_hours'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_office_hours',
      'type' => 'office_hours',
      'entity_type' => 'entity_test',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'element_type' => 'office_hours_datelist',
      ],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        // @todo Test all settings.
        'cardinality_per_day' => 2,
        // 'time_format' => 'G',
        // 'increment' => 30,
        // 'comment' => 2,
        // 'valhrs' => false,
        // 'required_start' => false,
        // 'required_end' => false,
        // 'limit_start' => '',
        // 'limit_end' => '',
      ],
      'default_value' => [
        [
          'day' => 0,
          'starthours' => 900,
          'endhours' => 1730,
          'comment' => 'Test comment',
        ],
        [
          'day' => 1,
          'starthours' => 700,
          'endhours' => 1800,
          'comment' => 'Test comment',
        ],
      ],
    ]);
    $this->field->save();

    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $entity_display */
    $entity_display = EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
    ]);
    // Save the office hours field to check if the config schema is valid.
    // @todo D9 test
    // Table formatter.
    $entity_display->setComponent('field_office_hours', ['type' => 'office_hours_table']);
    $entity_display->save();
    // Default formatter.
    $entity_display->setComponent('field_office_hours', ['type' => 'office_hours']);
    $entity_display->save();
  }

  /**
   * Tests the Office Hours field can be added to an entity type.
   */
  public function testOfficeHoursField() {
    $this->fieldStorage->setSetting('element_type', 'office_hours_datelist');
    $this->fieldStorage->save();

    // Verify entity creation.
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create();
    $field_name = 'field_office_hours';
    $value = [
      [
        'day' => '2',
        'starthours' => '1330',
        'endhours' => '2000',
        'comment' => '',
      ],
      [
        'day' => '3',
        'starthours' => '900',
        'endhours' => '2000',
        'comment' => '',
      ],
    ];
    $entity->set($field_name, $value);
    $entity->setName($this->randomMachineName());
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->get($field_name));
    $this->assertInstanceOf(FieldItemInterface::class, $entity->get($field_name)->first());

    // Verify changing the field value.
    $new_value = [
      // Normal day with 1 time slot.
      [
        'day' => '0',
        'all_day' => FALSE,
        'starthours' => '1430',
        'endhours' => '2000',
        'comment' => '',
      ],
      // @todo Normal day with multiple time slots.
      //
      // 'all_day' is checked, hours will be overwritten.
      [
        'day' => '1',
        'all_day' => TRUE,
        'starthours' => '1100',
        'endhours' => '1330',
        'comment' => '',
      ],
      // 'all_day' is not checked, user sets 00:00-00:00.
      [
        'day' => '2',
        'starthours' => '0000',
        'endhours' => '0000',
        'comment' => '',
      ],
      // Weekday without hours, with comment.
      [
        'day' => '3',
        'comment' => 'An empty weekday with comment',
      ],
      // Weekday without hours, without comment.
      [
        'day' => '4',
      ],
      /*
      [
        'day' => '5',
        // 'all_day' => TRUE,
        'starthours' => '',
        'endhours' => '',
        'comment' => '',
      ],
       */
    ];
    $entity->$field_name->setValue($new_value);

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    // Normal day with 1 time slot.
    $index = 0;
    $test_value = $entity->$field_name->first()->getValue();
    $test_value = implode('/', $test_value);
    $this->assertEquals(implode('/', $new_value[$index]), $test_value);
    // All_day, hours will be overwritten.
    $index = 1;
    $test_value = $entity->$field_name->get($index)->getValue();
    $test_value = implode('/', $test_value);
    $this->assertEquals('1/1/0/0/', $test_value);
    // 'all_day' is not checked, user sets 00:00-00:00.
    $index = 2;
    $test_value = $entity->$field_name->get($index)->getValue();
    $test_value = implode('/', $test_value);
    $this->assertEquals('2/1/0/0/', $test_value);
    // Weekday without hours, with comment.
    $index = 3;
    $test_value = $entity->$field_name->get($index)->getValue();
    $test_value = implode('/', $test_value);
    $this->assertEquals('3////An empty weekday with comment', $test_value);
    // Weekday without hours, without comment. This is not stored.
    $index = 4;
    $test_value = $entity->$field_name->get($index);
    $this->assertEquals(NULL, $test_value);

    // @todo Add tests for Exception day, in each of above cases.
    // ...
    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->$field_name->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
