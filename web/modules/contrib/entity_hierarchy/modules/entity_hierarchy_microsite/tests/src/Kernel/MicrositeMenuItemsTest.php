<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Kernel;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;
use Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverride;
use Drupal\Tests\entity_hierarchy_microsite\Traits\MenuRebuildTrait;

/**
 * Defines a class for testing microsite menu items.
 *
 * @group entity_hierarchy_microsite
 */
class MicrositeMenuItemsTest extends EntityHierarchyMicrositeKernelTestBase {

  use MenuRebuildTrait;

  const STATE_KEY = 'entity_hierarchy_microsite_test_rebuild_count';

  /**
   * Tests the microsite menu link integration.
   */
  public function testMicrositeMenuLinkDerivation(): void {
    \Drupal::state()->set('entity_hierarchy_microsite_max_depth', 2);
    $media = $this->createImageMedia();
    $children = $this->createChildEntities($this->parent->id(), 5);
    [$first, $second] = array_values($children);
    $first_children = $this->createChildEntities($first->id(), 5, '1.');
    $second_children = $this->createChildEntities($second->id(), 4, '2.');
    $microsite = Microsite::create([
      'name' => 'Subsite',
      'home' => $this->parent,
      'logo' => $media,
    ]);
    $last_second_child = end($second_children);
    // Create an item that is too deep.
    $this->createChildEntities($last_second_child->id(), 4, '2.4.');
    $microsite->save();
    // There should be no menus generated.
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $tree */
    $tree = \Drupal::service('menu.link_tree');
    $params = $tree->getCurrentRouteMenuTreeParameters('entity-hierarchy-microsite');
    $params->setMaxDepth(9);
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->assertCount(0, $items);

    // Set the generate menu flag.
    $microsite->set('generate_menu', TRUE)->save();
    $this->triggerMenuRebuild();

    // hook_entity_hierarchy_microsite_links_alter() should now be fired.
    $this->assertEquals('success', \Drupal::state()->get('entity_hierarchy_microsite_test_entity_hierarchy_microsite_links_alter', NULL));
    $this->assertEquals(1, \Drupal::state()->get(self::STATE_KEY));

    // Resave an item in the menu without changing the parent/weight.
    $last_second_child->save();
    $this->triggerMenuRebuild();
    // We shouldn't have regenerated the menu as nothing changed.
    $this->assertEquals(1, \Drupal::state()->get(self::STATE_KEY));

    // Change the weight of the last_second item.
    $last_second_child->{self::FIELD_NAME}->weight = $last_second_child->{self::FIELD_NAME}->weight + 1;
    $last_second_child->save();
    $this->triggerMenuRebuild();

    // We should have regenerated the menu as the weight changed.
    $this->assertEquals(2, \Drupal::state()->get(self::STATE_KEY));

    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $tree */
    $tree = \Drupal::service('menu.link_tree');
    $params = $tree->getCurrentRouteMenuTreeParameters('entity-hierarchy-microsite');
    $params->setMaxDepth(9);
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->triggerMenuRebuild();
    // A rebuild is triggered here from the route builder.
    $this->assertEquals(3, \Drupal::state()->get(self::STATE_KEY));
    $this->assertCount(1, $items);
    $plugin_id = 'entity_hierarchy_microsite:' . $this->parent->uuid();
    $this->assertArrayHasKey($plugin_id, $items);
    $this->assertCount(5, $items[$plugin_id]->subtree);
    foreach ($children as $entity) {
      $child_plugin_id = 'entity_hierarchy_microsite:' . $entity->uuid();
      $this->assertArrayHasKey($child_plugin_id, $items[$plugin_id]->subtree);
      if ($entity->uuid() === $first->uuid()) {
        $this->assertCount(5, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
        foreach ($first_children as $child_entity) {
          $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
        }
      }
      if ($entity->uuid() === $second->uuid()) {
        $this->assertCount(4, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
        foreach ($second_children as $child_entity) {
          $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
          if ($child_entity->uuid() === $last_second_child->uuid()) {
            $this->assertEmpty($items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $child_entity->uuid()]->subtree);
          }
        }
      }
    }
    /** @var \Drupal\node\NodeInterface $last */
    $last = array_pop($second_children);
    array_push($first_children, $last);
    $last->{self::FIELD_NAME} = $first;
    $last->save();
    $this->triggerMenuRebuild();

    // Should have caused another rebuild.
    $this->assertEquals(4, \Drupal::state()->get(self::STATE_KEY));
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $child_plugin_id = 'entity_hierarchy_microsite:' . $first->uuid();
    $this->assertCount(6, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($first_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }
    $child_plugin_id = 'entity_hierarchy_microsite:' . $second->uuid();
    $this->assertCount(3, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($second_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }

    $last = array_pop($second_children);
    // Create a new revision.
    $last->{self::FIELD_NAME} = NULL;
    $last->setNewRevision(TRUE);
    $last->save();
    $this->triggerMenuRebuild();
    $last->delete();
    $this->triggerMenuRebuild();

    $this->assertEquals(4, \Drupal::state()->get(self::STATE_KEY));
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->assertCount(2, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($second_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }

    // Update child and make sure no items have been re-parented.
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->triggerMenuRebuild();
    $this->assertCount(5, $items[$plugin_id]->subtree);
    $first->set('title', 'Updated first title')->setNewRevision();

    $first->save();
    $this->triggerMenuRebuild();

    $this->assertEquals(4, \Drupal::state()->get(self::STATE_KEY));
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->triggerMenuRebuild();
    $this->assertCount(5, $items[$plugin_id]->subtree);

    $lastChildOfSecond = end($second_children);
    $override1 = MicrositeMenuItemOverride::create([
      'target' => $lastChildOfSecond->uuid(),
      'enabled' => FALSE,
      'weight' => 1000,
      'title' => $lastChildOfSecond->label(),
      'parent' => 'entity_hierarchy_microsite:' . $second->uuid(),
    ]);
    $override1->save();
    $this->triggerMenuRebuild();
    $moved = reset($second_children);
    $override2 = MicrositeMenuItemOverride::create([
      'target' => $moved->uuid(),
      'weight' => -1000,
      'title' => 'Some other title',
      'parent' => 'entity_hierarchy_microsite:' . $first->uuid(),
    ]);
    $override2->save();
    $this->triggerMenuRebuild();
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $child_plugin_id = 'entity_hierarchy_microsite:' . $first->uuid();
    $this->assertCount(7, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($first_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }
    $this->assertArrayHasKey('entity_hierarchy_microsite:' . $moved->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    $this->assertEquals('Some other title', $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $moved->uuid()]->link->getTitle());
    $this->assertEquals('-1000', $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $moved->uuid()]->link->getWeight());
    $child_plugin_id = 'entity_hierarchy_microsite:' . $second->uuid();
    $this->assertCount(1, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    $this->assertFalse((bool) $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $lastChildOfSecond->uuid()]->link->isEnabled());
    $this->assertEquals('some-data', $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $lastChildOfSecond->uuid()]->link->getUrlObject()->getOption('attributes')['data-some-data']);
  }

  /**
   * Tests microsite menus do not exceed the maximum depth.
   */
  public function testMicrositeMenuLinkMaxDepth(): void {
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree */
    $menu_link_tree = \Drupal::service('menu.link_tree');
    $menu_max_depth = $menu_link_tree->maxDepth();
    $entity_max_depth = $menu_max_depth + 1;

    $media = $this->createImageMedia();
    $parent_id = $this->parent->id();
    for ($i = 1; $i <= $entity_max_depth; $i++) {
      $child = $this->createTestEntity($parent_id, 1, "{$i}.");
      $parent_id = $child->id();
    }
    $microsite = Microsite::create([
      'name' => 'Subsite',
      'generate_menu' => TRUE,
      'home' => $this->parent,
      'logo' => $media,
    ]);
    $microsite->save();
    $this->triggerMenuRebuild();

    // Menu depth should not exceed the maximum supported depth.
    $plugin_id = 'entity_hierarchy_microsite:' . $this->parent->uuid();
    $this->assertEquals($menu_max_depth, $menu_link_tree->getSubtreeHeight($plugin_id));

    // Microsite should still have descendants beyond the maximum supported
    // depth.
    $descendants = $this->treeStorage->findDescendants($this->parentStub);
    $this->assertEquals($entity_max_depth, end($descendants)->getDepth());
  }

}
