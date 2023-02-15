<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_hierarchy_microsite\Functional;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Defines a class for testing update path.
 *
 * @group entity_hierarchy
 * @group entity_hierarchy_microsite
 */
final class GenerateMenuUpgradePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 2) . '/fixtures/3284026-dump.sql.gz',
    ];
  }

  /**
   * Tests update path.
   */
  public function testUpdatePath(): void {
    $microsites = Microsite::loadMultiple();
    $microsite = reset($microsites);
    $this->assertEquals('Grandparent', $microsite->label());
    $items = \Drupal::database()->select('menu_tree', 'mt')
      ->fields('mt', ['discovered', 'title'])
      ->condition('menu_name', 'entity-hierarchy-microsite')
      ->execute()
      ->fetchAllAssoc('title');
    $items = array_combine(array_map(function (string $key) {
      return unserialize($key, ['allowed_classes' => FALSE]);
    }, array_keys($items)), array_map(function ($row) {
      return $row->discovered;
    }, $items));
    $this->assertEquals([
      'Grandparent' => 0,
      'Parent' => 0,
      'Child' => 0,
      'Manual link' => 0,
    ], $items);
    $this->runUpdates();
    $microsite = \Drupal::entityTypeManager()->getStorage('entity_hierarchy_microsite')->loadUnchanged($microsite->id());
    $this->assertTrue($microsite->shouldGenerateMenu());
    $items = \Drupal::database()->select('menu_tree', 'mt')
      ->fields('mt', ['discovered', 'title'])
      ->condition('menu_name', 'entity-hierarchy-microsite')
      ->execute()
      ->fetchAllAssoc('title');
    $items = array_combine(array_map(function (string $key) {
      return unserialize($key, ['allowed_classes' => FALSE]);
    }, array_keys($items)), array_map(function ($row) {
      return $row->discovered;
    }, $items));
    $this->assertEquals([
      'Grandparent' => 1,
      'Parent' => 1,
      'Manual link' => 1,
    ], $items);
  }

}
