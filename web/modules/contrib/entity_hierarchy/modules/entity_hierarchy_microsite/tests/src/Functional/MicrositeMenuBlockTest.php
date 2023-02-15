<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Functional;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;
use Drupal\Tests\entity_hierarchy_microsite\Traits\MenuRebuildTrait;

/**
 * Defines a class for testing microsite menu block.
 *
 * @group entity_hierarchy_microsite
 */
class MicrositeMenuBlockTest extends MicrositeFunctionalTestBase {

  use MenuRebuildTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('entity_hierarchy_microsite_menu', [
      'field' => self::FIELD_NAME,
      'id' => 'microsite_menu',
      'context_mapping' => [
        'node' => '@node.node_route_context:node',
      ],
      'region' => 'content',
      'visibility' => [
        'entity_hierarchy_microsite_child' => [
          'id' => 'entity_hierarchy_microsite_child',
          'field' => self::FIELD_NAME,
          'negate' => FALSE,
          'context_mapping' => [
            'node' => '@node.node_route_context:node',
          ],
        ],
      ],
    ]);
  }

  /**
   * Tests menu block.
   */
  public function testMenuBlock() {
    $logo = $this->createImageMedia();
    $root = $this->createTestEntity(NULL, 'Root');
    $children = $this->createChildEntities($root->id());
    $microsite = Microsite::create([
      'name' => $root->label(),
      'home' => $root,
      'logo' => $logo,
      'generate_menu' => TRUE,
    ]);
    $microsite->save();
    $this->triggerMenuRebuild();
    $this->drupalGet($root->toUrl());
    $assert = $this->assertSession();
    $menu = $assert->elementExists('css', '#block-microsite-menu ul');
    $links = $menu->findAll('css', 'li a');
    $this->assertCount(5, $links);
    foreach ($children as $child) {
      $this->assertNotEmpty($menu->find('named', ['link', $child->label()]));
      $assert->linkExists($child->label());
      $xpath = $this->assertSession()->buildXPathQuery('//a[contains(@href, :href)]', [':href' => $child->toUrl()->toString()]);
      $this->assertNotEmpty($menu->find('xpath', $xpath));
    }
  }

}
