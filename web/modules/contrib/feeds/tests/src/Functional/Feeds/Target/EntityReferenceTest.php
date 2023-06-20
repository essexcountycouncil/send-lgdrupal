<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Target;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\EntityReference
 * @group feeds
 */
class EntityReferenceTest extends FeedsBrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
    'taxonomy',
  ];

  /**
   * Tests that a bundle can get selected when autocreating terms.
   */
  public function testAutocreateBundleSetting() {
    $vocabulary_storage = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_vocabulary');

    // Create a few vocabularies.
    $vocabulary_storage->create([
      'vid' => 'foo',
      'name' => 'Foo',
    ])->save();
    $vocabulary_storage->create([
      'vid' => 'bar',
      'name' => 'Bar',
    ])->save();
    $vocabulary_storage->create([
      'vid' => 'qux',
      'name' => 'Qux',
    ])->save();

    // Create an entityreference field on article, select only 2 of the
    // available taxonomies.
    $this->createEntityReferenceField('node', 'article', 'field_term', 'Term', 'taxonomy_term', 'default', [
      'target_bundles' => ['foo', 'qux'],
    ]);

    // Create a feed type.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);

    // Go to the mapping page, and a target to 'field_term'.
    $edit = [
      'add_target' => 'field_term',
    ];
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping');
    $this->submitForm($edit, 'Save');

    $edit = [];
    $this->submitForm($edit, 'target-settings-2');

    // Assert that options 'foo' and 'qux' exist for "autocreate_bundle"
    // but 'bar' does not.
    $this->assertSession()->optionExists('mappings[2][settings][autocreate_bundle]', 'foo');
    $this->assertSession()->optionExists('mappings[2][settings][autocreate_bundle]', 'qux');
    $this->assertSession()->optionNotExists('mappings[2][settings][autocreate_bundle]', 'bar');
  }

}
