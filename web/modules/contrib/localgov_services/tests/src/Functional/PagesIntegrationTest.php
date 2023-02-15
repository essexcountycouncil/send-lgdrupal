<?php

namespace Drupal\Tests\localgov_services\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests localgov services pages working together, and with external modules.
 *
 * @group media_counter
 */
class PagesIntegrationTest extends BrowserTestBase {

  use NodeCreationTrait;
  use AssertBreadcrumbTrait;
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
    'localgov_services',
    'localgov_services_landing',
    'localgov_services_sublanding',
    'localgov_services_page',
    'localgov_services_navigation',
    'localgov_topics',
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
   * Post and link test.
   *
   * Post a service landing page.
   * Post a service sub landing page, and link to landing page.
   * Link landing page to sublanding page.
   * Post a page, put it in the landing and sublanding services.
   * Link page from sublanding page.
   */
  public function testPostLink() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/localgov_services_landing');
    $form = $this->getSession()->getPage();
    $form->fillField('edit-title-0-value', 'Service 1');
    $form->fillField('edit-body-0-summary', 'Service 1 summary');
    $form->fillField('edit-body-0-value', 'Service 1 description');
    $form->checkField('edit-status-value');
    $form->pressButton('edit-submit');

    $this->drupalGet('node/add/localgov_services_sublanding');
    $form = $this->getSession()->getPage();
    $form->fillField('edit-title-0-value', 'Sub Service 1');
    $form->fillField('edit-body-0-summary', 'Sub Service 1 summary');
    $form->fillField('edit-body-0-value', 'Sub Service 1 description');
    $form->fillField('edit-localgov-services-parent-0-target-id', 'Service 1 (1)');
    $form->checkField('edit-status-value');
    $form->pressButton('edit-submit');

    $this->drupalGet('node/1/edit');
    $form = $this->getSession()->getPage();
    $form->fillField('edit-localgov-destinations-0-target-id', 'Sub landing 1 (2)');
    $form->pressButton('edit-submit');

    $this->drupalGet('node/add/localgov_services_page');
    $assert = $this->assertSession();
    $form = $this->getSession()->getPage();
    $form->fillField('edit-title-0-value', 'Service 1 Page 1');
    $form->fillField('edit-body-0-summary', 'Service 1 summary 1 ');
    $form->fillField('edit-body-0-value', 'Service 1 description 1');
    $form->fillField('edit-localgov-services-parent-0-target-id', 'Service 1 » Sub landing 1 (2)');
    $form->checkField('edit-status-value');
    $form->pressButton('edit-submit');

    $this->drupalGet('node/2/edit');
    $form = $this->getSession()->getPage();
    $form->fillField('edit-localgov-topics-0-subform-topic-list-links-0-uri', '/node/3');
    $form->pressButton('edit-submit');

    $assert = $this->assertSession();
    $assert->pageTextContains('Service 1 Page 1');
  }

  /**
   * Path test.
   */
  public function testServicePaths() {
    $node = $this->createNode([
      'title' => 'Landing Page 1',
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('landing-page-1');
    $this->assertSession()->pageTextContains('Landing Page 1');
    $trail = ['' => 'Home'];
    $this->assertBreadcrumb(NULL, $trail);

    $node = $this->createNode([
      'title' => 'Sublanding 1',
      'type' => 'localgov_services_sublanding',
      'status' => NodeInterface::PUBLISHED,
      'localgov_services_parent' => ['target_id' => $node->id()],
    ]);
    $this->drupalGet('landing-page-1/sublanding-1');
    $this->assertSession()->pageTextContains('Sublanding 1');
    $trail += ['landing-page-1' => 'Landing Page 1'];
    $this->assertBreadcrumb(NULL, $trail);

    $this->createNode([
      'title' => 'Service Page 1',
      'type' => 'localgov_services_page',
      'status' => NodeInterface::PUBLISHED,
      'localgov_services_parent' => ['target_id' => $node->id()],
    ]);
    $this->drupalGet('landing-page-1/sublanding-1/service-page-1');
    $this->assertSession()->pageTextContains('Service Page 1');
    $trail += ['landing-page-1/sublanding-1' => 'Sublanding 1'];
    $this->assertBreadcrumb(NULL, $trail);
  }

  /**
   * LocalGov Search integration.
   */
  public function testLocalgovSearch() {
    $title = 'Test Page';
    $body = [
      'value' => 'Science is the search for truth, that is the effort to understand the world: it involves the rejection of bias, of dogma, of revelation, but not the rejection of morality.',
      'summary' => 'One of the greatest joys known to man is to take a flight into ignorance in search of knowledge.',
    ];
    $this->createNode([
      'title' => $title,
      'body' => $body,
      'type' => 'localgov_services_page',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->cronRun();

    $this->drupalGet('search', ['query' => ['s' => 'bias+dogma+revelation']]);
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->responseContains('<strong>bias</strong>');
    $this->assertSession()->responseContains('<strong>dogma</strong>');
    $this->assertSession()->responseContains('<strong>revelation</strong>');
  }

}
