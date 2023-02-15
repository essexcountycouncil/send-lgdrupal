<?php

namespace Drupal\Tests\localgov_step_by_step\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests user blocks.
 *
 * @group localgov_campaigns
 */
class StepByStepBlocksTest extends BrowserTestBase {

  use NodeCreationTrait;
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_step_by_step',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * A user with the 'administer blocks' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

    $this->adminUser = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('step_part_of_block');
    $this->drupalLogout();
  }

  /**
   * Test banner block.
   */
  public function testPartOfStepBlock() {

    // Create some nodes.
    $overview_title = $this->randomMachineName(8);
    $overview = $this->createNode([
      'title' => $overview_title,
      'type' => 'localgov_step_by_step_overview',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $page = $this->createNode([
      'title' => 'Test page',
      'type' => 'localgov_step_by_step_page',
      'status' => NodeInterface::PUBLISHED,
      'localgov_step_parent' => ['target_id' => $overview->id()],
    ]);
    $article = $this->createNode([
      'title' => 'Test article',
      'type' => 'article',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalGet($overview->toUrl()->toString());
    $this->assertSession()->linkNotExists($overview_title);
    $this->assertSession()->pageTextNotContains('Part of');
    $this->drupalGet($page->toUrl()->toString());
    $this->assertSession()->pageTextContains('Part of');
    $this->assertSession()->linkExists($overview_title);
    $this->drupalGet($article->toUrl()->toString());
    $this->assertSession()->linkNotExists($overview_title);
    $this->assertSession()->pageTextNotContains('Part of');
  }

}
