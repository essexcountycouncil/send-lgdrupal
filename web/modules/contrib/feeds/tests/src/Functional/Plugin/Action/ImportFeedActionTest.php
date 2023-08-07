<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Action;

use Drupal\feeds\Entity\Feed;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Plugin\Action\ImportFeedAction
 * @group feeds
 */
class ImportFeedActionTest extends FeedsBrowserTestBase {

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
   * Tests applying action "feeds_feed_import_action" on feed entities.
   */
  public function test() {
    // Add a feed type.
    $feed_type = $this->createFeedType();

    // Create a few feeds.
    $feed1 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/drupalplanet.rss2',
    ]);
    $feed2 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $feed3 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/media-rss.rss2',
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');

    // Select the first two feeds.
    $edit = [];
    for ($i = 0; $i < 2; $i++) {
      $this->assertSession()->fieldExists('edit-feeds-feed-bulk-form-' . $i);
      $edit["feeds_feed_bulk_form[$i]"] = TRUE;
    }

    // Import the selected feeds.
    $edit += ['action' => 'feeds_feed_import_action'];
    $this->submitForm($edit, 'Apply to selected items');

    // Assert a confirmation page is shown.
    $this->assertSession()->pageTextContains('Are you sure you want to import the selected feeds?');
    $this->submitForm([], 'Import');

    // Assert that feed 1 and feed 2 got imported, but feed 3 was not.
    $this->container->get('entity_type.manager')
      ->getStorage('feeds_feed')
      ->resetCache();

    $feed1 = Feed::load(1);
    $this->assertEquals(25, $feed1->item_count->value);
    $feed2 = Feed::load(2);
    $this->assertEquals(6, $feed2->item_count->value);
    $feed3 = Feed::load(3);
    $this->assertEquals(0, $feed3->item_count->value);

    $assert = $this->assertSession();
    $assert->pageTextContains('Imported 2 feeds.');
    $assert->pageTextContains($feed1->label() . ': Created 25 Article items.');
    $assert->pageTextContains($feed2->label() . ': Created 6 Article items.');
    $assert->pageTextNotContains($feed3->label() . ': Created 6 Article items.');
  }

}
