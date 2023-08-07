<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Tests the clear multiple confirmation form.
 *
 * @group feeds
 */
class ClearMultipleFormTest extends FeedsBrowserTestBase {

  /**
   * The feed type used for testing.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The first created feed.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed1;

  /**
   * The second created feed.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
    ]);

    // Create two feeds and import content for both.
    $this->feed1 = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $this->feed1->import();
    $this->assertNodeCount(25);

    $this->feed2 = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->feed2->import();
    $this->assertNodeCount(31);
  }

  /**
   * Tests the feed clear form as admin.
   */
  public function testWithAdminPrivileges() {
    // Add the selection to the tempstore just like ClearFeedAction would.
    $selection[$this->feed1->id()] = $this->feed1->id();
    $selection[$this->feed2->id()] = $this->feed2->id();
    $tempstore = $this->container->get('tempstore.private')->get('feeds_feed_multiple_clear_confirm');
    $tempstore->set($this->adminUser->id() . ':feeds_feed', $selection);

    $this->drupalGet('/admin/content/feed/clear');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', 'h1', 'Are you sure you want to delete all imported items of the selected feeds?');
    $assert->pageTextContains($this->feed1->label());
    $assert->pageTextContains($this->feed2->label());
    $this->submitForm([], 'Delete items');
    $assert = $this->assertSession();
    $assert->addressEquals('/admin/content/feed');
    $assert->pageTextContains('Deleted items of 2 feeds.');
    $assert->pageTextContains('Deleted 25 Article items from ' . $this->feed1->label());
    $assert->pageTextContains('Deleted 6 Article items from ' . $this->feed2->label());
    $this->assertNodeCount(0);

    // Assert that the tempstore is now empty.
    $this->assertNull($tempstore->get($this->adminUser->id() . ':feeds_feed'));
  }

  /**
   * Tests the feed clear form with limited privileges.
   *
   * The logged in user may only clear items from feed 1, not feed 2.
   */
  public function testWithLimitedPrivileges() {
    // Create a user who may only delete items from feed 1.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'clear own ' . $this->feedType->id() . ' feeds',
      'view ' . $this->feedType->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Set owner of feed 1.
    $this->feed1->uid = $account->id();
    $this->feed1->save();

    // Add the selection to the tempstore just like ClearFeedAction would.
    $selection[$this->feed1->id()] = $this->feed1->id();
    $selection[$this->feed2->id()] = $this->feed2->id();
    $tempstore = $this->container->get('tempstore.private')->get('feeds_feed_multiple_clear_confirm');
    $tempstore->set($account->id() . ':feeds_feed', $selection);

    $this->drupalGet('/admin/content/feed/clear');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', 'h1', 'Are you sure you want to delete all imported items of the selected feeds?');
    $assert->pageTextContains($this->feed1->label());
    $assert->pageTextContains($this->feed2->label());
    $this->submitForm([], 'Delete items');
    $assert = $this->assertSession();
    $assert->addressEquals('/admin/content/feed');
    $assert->pageTextContains('Deleted items of 1 feed.');
    $assert->pageTextContains('Deleted 25 Article items from ' . $this->feed1->label());
    $assert->pageTextNotContains('Deleted 6 Article items from ' . $this->feed2->label());
    $assert->pageTextContains('1 feed has not been cleared because you do not have the necessary permissions.');
    $this->assertNodeCount(6);

    // Assert that the tempstore is now empty.
    $this->assertNull($tempstore->get($account->id() . ':feeds_feed'));
  }

}
