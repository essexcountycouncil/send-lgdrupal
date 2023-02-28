<?php

namespace Drupal\Tests\localgov_services_status\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Tests localgov service status pages.
 *
 * @group localgov_services
 */
class ServiceStatusTest extends BrowserTestBase {

  use AssertBreadcrumbTrait;
  use NodeCreationTrait;

  /**
   * Use testing profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Use stark theme.
   *
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'path',
    'localgov_services_status',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access content overview',
      'administer content types',
      'administer node fields',
      'administer nodes',
      'bypass node access',
      'create url aliases',
    ]);
  }

  /**
   * Test necessary fields have been added.
   */
  public function testServiceStatusPages() {
    $this->drupalLogin($this->adminUser);

    // Check all fields exist.
    $this->drupalGet('/admin/structure/types/manage/localgov_services_status/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('body');
    $this->assertSession()->pageTextContains('localgov_service_status');
    $this->assertSession()->pageTextContains('localgov_services_parent');
    $this->assertSession()->pageTextContains('localgov_service_status_on_landi');
    $this->assertSession()->pageTextContains('localgov_service_status_on_list');

    // Create a landing page.
    $landing = $this->createNode([
      'title' => 'Test Service',
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Create a status page.
    $title = $this->randomMachineName(8);
    $summary = $this->randomMachineName(16);
    $body = $this->randomMachineName(32);
    $status = $this->createNode([
      'type' => 'localgov_services_status',
      'title' => $title,
      'body' => [
        'summary' => $summary,
        'value' => $body,
      ],
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'localgov_service_status' => ['value' => '0-severe-impact'],
      'localgov_service_status_on_landi' => ['value' => 1],
      'localgov_service_status_on_list' => ['value' => 1],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/' . $status->id());
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains($body);

    // Check display on landing page.
    $this->drupalGet('/node/' . $landing->id());
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains($summary);
    $status->setUnpublished();
    $status->save();
    $this->drupalGet('/node/' . $landing->id());
    $this->assertSession()->pageTextNotContains($title);
    $this->assertSession()->pageTextNotContains($summary);
    $status->setPublished();
    $status->save();
    $this->drupalGet('/node/' . $landing->id());
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains($summary);
    $status->set('localgov_service_status_on_landi', ['value' => 0]);
    $status->save();
    $this->drupalGet('/node/' . $landing->id());
    $this->assertSession()->pageTextNotContains($title);
    $this->assertSession()->pageTextNotContains($summary);
  }

  /**
   * Test listings.
   */
  public function testServiceStatusListings() {
    $this->drupalLogin($this->adminUser);

    // Create a landing page.
    $landing = $this->createNode([
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Create some status updates.
    $status = [];
    for ($i = 1; $i <= 3; $i++) {
      $status[$i] = $this->createNode([
        'type' => 'localgov_services_status',
        'title' => 'Test Status ' . $i,
        'body' => [
          'summary' => 'Test status summary ' . $i,
          'value' => 'Test status body ' . $i,
        ],
        'localgov_services_parent' => ['target_id' => $landing->id()],
        'localgov_service_status' => ['value' => '0-severe-impact'],
        'localgov_service_status_on_landi' => ['value' => 1],
        'localgov_service_status_on_list' => ['value' => 1],
        'status' => NodeInterface::PUBLISHED,
      ]);
    }

    // Check service status updates page.
    $this->drupalGet('node/' . $landing->id() . '/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test Status 1');
    $this->assertSession()->pageTextContains('Test Status 2');
    $this->assertSession()->pageTextContains('Test Status 3');

    // Check the service-status page.
    $this->drupalGet('/service-status');
    $this->assertSession()->statusCodeEquals(200);
    $xpath = '//ul[@id="tabs"]/li/a';
    /** @var \Behat\Mink\Element\NodeElement[] $results */
    $results = $this->xpath($xpath);
    $this->assertStringContainsString('Test Status 1', $results[0]->getText());
    $this->assertStringContainsString('Test Status 2', $results[1]->getText());
    $this->assertStringContainsString('Test Status 3', $results[2]->getText());

    // Check service-status page title.
    $this->drupalPlaceBlock('localgov_page_header_block');
    $this->assertSession()->responseContains('<h1>Council service updates</h1>');
    $this->assertSession()->responseNotMatches('/<h1?.*>Service status<\/h1>/');

    // Check sticky on top works.
    $status[3]->setSticky(TRUE);
    $status[3]->save();
    $this->drupalGet('/service-status');
    $xpath = '//ul[@id="tabs"]/li/a';
    /** @var \Behat\Mink\Element\NodeElement[] $results */
    $results = $this->xpath($xpath);
    $this->assertStringContainsString('Test Status 3', $results[2]->getText());
    $this->assertStringContainsString('Test Status 1', $results[0]->getText());
    $this->assertStringContainsString('Test Status 2', $results[1]->getText());

    // Check unpublish.
    $status[2]->setUnpublished();
    $status[2]->save();
    $this->drupalGet('/node/' . $landing->id() . '/status');
    $this->assertSession()->pageTextNotContains('Test Status 2');
    $this->drupalGet('/service-status');
    $this->assertSession()->pageTextNotContains('Test Status 2');

    // Check hide from lists.
    $status[1]->set('localgov_service_status_on_list', ['value' => 0]);
    $status[1]->save();
    $this->drupalGet('/node/' . $landing->id() . '/status');
    $this->assertSession()->pageTextNotContains('Test Status 1');
    $this->drupalGet('/service-status');
    $this->assertSession()->pageTextNotContains('Test Status 1');

    // Check service status updates page with no valid statuses.
    $status[3]->set('localgov_service_status_on_list', ['value' => 0]);
    $status[3]->save();
    $this->drupalGet('node/' . $landing->id() . '/status');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test paths.
   *
   * @see \Drupal\Tests\localgov_services_status\Kernel\PathTest
   */
  public function testServiceStatusPath() {
    // Create a landing page.
    $landing = $this->createNode([
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $status = $this->createNode([
      'type' => 'localgov_services_status',
      'title' => 'Test Status',
      'body' => [
        'summary' => 'Test status summary',
        'value' => 'Test status body',
      ],
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'localgov_service_status' => ['value' => '0-severe-impact'],
      'localgov_service_status_on_landi' => ['value' => 1],
      'localgov_service_status_on_list' => ['value' => 1],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $landing->id());
    $this->drupalGet($alias . '/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test Status');

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $status_alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $status->id());
    $this->assertEquals($status_alias, $alias . '/status/test-status');
    $this->drupalGet($alias . '/status/test-status');
    $trail = ['' => 'Home'];
    $trail += [$alias => $landing->getTitle()];
    $trail += [$alias . '/status' => 'Latest service status'];
    $this->assertBreadcrumb(NULL, $trail);
  }

}
