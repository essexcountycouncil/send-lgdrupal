<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Action;

use Drupal\feeds\Entity\Feed;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Plugin\Action\ClearFeedAction
 * @group feeds
 */
class ClearFeedActionTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
    'views',
  ];

  /**
   * Tests applying action "feeds_feed_clear_action" on feed entities.
   */
  public function test() {
    // Add a feed type.
    $feed_type = $this->createFeedType();

    // Create a few feeds and import content for them.
    $feed1 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/drupalplanet.rss2',
    ]);
    $feed1->import();
    $this->assertNodeCount(25);

    $feed2 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $feed2->import();
    $this->assertNodeCount(31);

    $feed3 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/media-rss.rss2',
    ]);
    $feed3->import();
    $this->assertNodeCount(37);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');

    // Select the first two feeds.
    $edit = [];
    for ($i = 0; $i < 2; $i++) {
      $this->assertSession()->fieldExists('edit-feeds-feed-bulk-form-' . $i);
      $edit["feeds_feed_bulk_form[$i]"] = TRUE;
    }

    // Clear the selected feeds.
    $edit += ['action' => 'feeds_feed_clear_action'];
    $this->submitForm($edit, 'Apply to selected items');

    // Assert a confirmation page is shown.
    $this->assertSession()->pageTextContains('Are you sure you want to delete all imported items of the selected feeds?');
    $this->submitForm([], 'Delete items');

    // Assert that feed 1 and feed 2 no longer have imported items, but feed 3
    // still does.
    $this->container->get('entity_type.manager')
      ->getStorage('feeds_feed')
      ->resetCache();

    $feed1 = Feed::load(1);
    $this->assertEquals(0, $feed1->item_count->value);
    $feed2 = Feed::load(2);
    $this->assertEquals(0, $feed2->item_count->value);
    $feed3 = Feed::load(3);
    $this->assertEquals(6, $feed3->item_count->value);

    $assert = $this->assertSession();
    $assert->pageTextContains('Deleted items of 2 feeds.');
    $assert->pageTextContains('Deleted 25 Article items from ' . $feed1->label());
    $assert->pageTextContains('Deleted 6 Article items from ' . $feed2->label());
    $assert->pageTextNotContains('Deleted 6 Article items from ' . $feed3->label());
  }

}
