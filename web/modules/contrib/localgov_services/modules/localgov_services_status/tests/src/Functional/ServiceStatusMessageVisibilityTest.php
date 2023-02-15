<?php

namespace Drupal\Tests\localgov_services_status\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests localgov service status messages.
 *
 * @group localgov_services
 */
class ServiceStatusMessageVisibilityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_services_status',
  ];

  /**
   * Test service status message visibility.
   */
  public function testServiceStatusMessageVisibility() {
    $this->drupalPlaceBlock('localgov_service_status_message');

    // Create a landing page.
    $landing = $this->createNode([
      'title' => 'Test Service',
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $landing_path = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $landing->id());

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
      'status' => NodeInterface::PUBLISHED,
      'localgov_service_status_visibile' => [
        'conditions' => [
          'request_path' => [
            'pages' => $landing_path,
            'negate' => 0,
          ],
        ],
      ],
    ]);

    // Check visibility on landing page.
    $this->drupalGet($landing_path);
    $this->assertSession()->elementExists('css', '.service-status-messages');
    $this->assertSession()->elementTextContains('css', '.service-status-messages', $summary);
    $this->assertSession()->elementTextNotContains('css', '.service-status-messages', $title);
    $this->assertSession()->elementTextNotContains('css', '.service-status-messages', $body);
    $this->assertSession()->linkByHrefExists(\Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $status->id()));

    // Check visibility on homepage.
    $this->drupalGet('<front>');
    $this->assertSession()->elementNotExists('css', '.service-status-messages');
    $this->assertSession()->pageTextNotContains($summary);
    $this->assertSession()->pageTextNotContains($title);
    $this->assertSession()->pageTextNotContains($body);

    // Create another status page.
    $title2 = 'Another status page';
    $summary2 = $this->randomMachineName(16);
    $status2 = $this->createNode([
      'type' => 'localgov_services_status',
      'title' => $title2,
      'body' => [
        'summary' => $summary2,
        'value' => '',
      ],
      'localgov_service_status' => ['value' => '1-has-issues'],
      'status' => NodeInterface::PUBLISHED,
      'localgov_service_status_visibile' => [
        'conditions' => [
          'request_path' => [
            'pages' => $landing_path . "\n<front>",
            'negate' => 0,
          ],
        ],
      ],
    ]);

    // This is only necessary in tests.
    // Block visibility is fine when doing this manually.
    drupal_flush_all_caches();

    // Check visibility on homepage.
    $this->drupalGet('<front>');
    $this->assertSession()->elementExists('css', '.service-status-messages');
    $this->assertSession()->pageTextNotContains($summary);
    $this->assertSession()->pageTextContains($summary2);

    // Check visibility on landing page.
    $this->drupalGet($landing_path);
    $this->assertSession()->elementTextContains('css', '.service-status-messages', $summary);
    $this->assertSession()->elementTextContains('css', '.service-status-messages', $summary2);
    $this->assertSession()->linkByHrefExists(\Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $status2->id()));
  }

}
