<?php

namespace Drupal\Tests\localgov_events\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests pages working together with search, pathauto, services and topics.
 *
 * @group localgov_step_by_step
 */
class LocalgovIntegrationTest extends BrowserTestBase {

  use NodeCreationTrait;
  use AssertBreadcrumbTrait;
  use TaxonomyTestTrait;
  use CronRunTrait;

  /**
   * Test breadcrumbs in the Standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_core',
    'localgov_services_landing',
    'localgov_services_sublanding',
    'localgov_services_navigation',
    'localgov_topics',
    'localgov_events',
    'localgov_search',
    'localgov_search_db',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
  }

  /**
   * LocalGov Search integration.
   */
  public function testLocalgovSearch() {
    $body = [
      'value' => 'Science is the search for truth, that is the effort to understand the world: it involves the rejection of bias, of dogma, of revelation, but not the rejection of morality.',
      'summary' => 'One of the greatest joys known to man is to take a flight into ignorance in search of knowledge.',
    ];
    $this->createNode([
      'title' => 'Test Event',
      'body' => $body,
      'type' => 'localgov_event',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->cronRun();

    $this->drupalGet('search', ['query' => ['s' => 'bias+dogma+revelation']]);
    $this->assertSession()->pageTextContains('Test Event');
    $this->assertSession()->responseContains('<strong>bias</strong>');
    $this->assertSession()->responseContains('<strong>dogma</strong>');
    $this->assertSession()->responseContains('<strong>revelation</strong>');
  }

}
