<?php

namespace Drupal\Tests\localgov_guides\Kernel;

use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Check maintaining integrity of backreference to children from overview.
 *
 * @group localgov_guides
 */
class OverviewPageIntegrity extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;
  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'link',
    'user',
    'node',
    'options',
    'filter',
    'localgov_core',
    'localgov_guides',
  ];

  /**
   * Service Landing page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $serviceLanding;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'filter',
      'system',
      'node',
      'localgov_guides',
    ]);

  }

  /**
   * Test programmatic parent addition.
   */
  public function testServiceParentAddition() {
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    $overviews = [];
    $overviews[0] = $this->createNode([
      'title' => 'Overview 1',
      'type' => 'localgov_guides_overview',
    ]);
    $overviews[1] = $this->createNode([
      'title' => 'Overview 2',
      'type' => 'localgov_guides_overview',
    ]);
    $pages = [];
    $pages[0] = $this->createNode([
      'title' => 'Page 1',
      'type' => 'localgov_guides_page',
      'localgov_guides_parent' => ['target_id' => $overviews[0]->id()],
    ]);
    $pages[1] = $this->createNode([
      'title' => 'Page 2',
      'type' => 'localgov_guides_page',
      'localgov_guides_parent' => ['target_id' => $overviews[0]->id()],
    ]);

    // Check the two created.
    $storage->resetCache([$overviews[0]->id()]);
    $overviews[0] = $storage->load($overviews[0]->id());
    $child_pages = $overviews[0]->get('localgov_guides_pages')->getValue();
    $this->assertTrue(array_search(['target_id' => $pages[0]->id()], $child_pages) !== FALSE);
    $this->assertTrue(array_search(['target_id' => $pages[1]->id()], $child_pages) !== FALSE);

    // While the overview is 'open' remove one node and add another.
    $pages[1]->set('localgov_guides_parent', ['target_id' => $overviews[1]->id()]);
    $pages[1]->save();
    $pages[2] = $this->createNode([
      'title' => 'Page 3',
      'type' => 'localgov_guides_page',
      'localgov_guides_parent' => ['target_id' => $overviews[0]->id()],
    ]);

    // Make sure it is saved with the original two pages.
    $overviews[0]->set('localgov_guides_pages', $child_pages);
    $overviews[0]->save();

    // Check that the two pages are those actually referencing now.
    $storage->resetCache([$overviews[0]->id()]);
    $overviews[0] = $storage->load($overviews[0]->id());
    $child_pages = $overviews[0]->get('localgov_guides_pages')->getValue();
    $this->assertTrue(array_search(['target_id' => $pages[0]->id()], $child_pages) !== FALSE);
    $this->assertTrue(array_search(['target_id' => $pages[1]->id()], $child_pages) === FALSE);
    $this->assertTrue(array_search(['target_id' => $pages[2]->id()], $child_pages) !== FALSE);
  }

}
