<?php

namespace Drupal\Tests\feeds\Functional\Update;

use Drupal\Core\Config\FileStorage;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;

/**
 * Provides a test to check adding action plugins.
 *
 * @group feeds
 * @group Update
 */
class AddActionUpdateTest extends UpdatePathTestBase {

  use FeedCreationTrait;
  use FeedsCommonTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['feeds', 'node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->getCoreFixturePath(9),
      __DIR__ . '/../../../fixtures/feeds-8.x-3.0-beta1-feeds_installed.php',
    ];
  }

  /**
   * Tests adding several new actions.
   *
   * Actions:
   * - feeds_feed_clear_action;
   * - feeds_feed_import_action.
   */
  public function testAddFeedActions() {
    // Run the updates.
    $this->runUpdates();

    // Install the feeds_feed view.
    $source = new FileStorage($this->absolutePath() . '/config/optional');
    $this->container->get('config.storage')
      ->write('views.view.feeds_feed', $source->read('views.view.feeds_feed'));
    drupal_flush_all_caches();

    // Add a feed type and feed.
    $feed_type = $this->createFeedType();
    $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/drupalplanet.rss2',
    ]);

    // Create a feeds admin user.
    $admin = $this->drupalCreateUser([
      'administer feeds',
      'access feed overview',
    ]);
    $this->drupalLogin($admin);

    // Ensure that the new action options are available now.
    $this->drupalGet('/admin/content/feed');
    $this->assertSession()->optionExists('action', 'feeds_feed_clear_action');
    $this->assertSession()->optionExists('action', 'feeds_feed_import_action');
  }

}
