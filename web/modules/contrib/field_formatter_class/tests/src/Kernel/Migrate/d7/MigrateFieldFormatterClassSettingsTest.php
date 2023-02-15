<?php

namespace Drupal\Tests\field_formatter_class\Kernel\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of Drupal 7 field formatter class settings.
 *
 * @group field_formatter_class
 */
class MigrateFieldFormatterClassSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_formatter_class',
    'field',
    'comment',
    'datetime',
    'image',
    'link',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'field_formatter_class'),
      'tests',
      'fixtures',
      'update',
      'drupal7.php',
    ]));
    $migrations = [
      'd7_field_instance',
      'd7_view_modes',
      'd7_field_formatter_settings',
      'd7_field_formatter_class',
    ];
    $this->executeMigrations($migrations);
  }

  /**
   * Asserts module aspects of a particular component of a view display.
   *
   * @param string $display_id
   *   The view display ID.
   * @param string $field_id
   *   The field ID.
   * @param string $class
   *   The expected class name.
   */
  protected function assertComponent($display_id, $field_id, $class) {
    $component = EntityViewDisplay::load($display_id)->getComponent($field_id);
    $this->assertIsArray($component);
    $this->assertIdentical($class, $component['third_party_settings']['field_formatter_class']['class']);
  }

  /**
   * Tests that all expected configuration gets migrated.
   */
  public function testConfigurationMigration() {
    $this->assertComponent('node.article.default', 'field_tags', 'classtest1');
    $this->assertComponent('node.article.default', 'field_image', 'classtest1 classtest2');
    $this->assertComponent('comment.comment_node_book.default', 'comment_body', 'class test');
  }

}
