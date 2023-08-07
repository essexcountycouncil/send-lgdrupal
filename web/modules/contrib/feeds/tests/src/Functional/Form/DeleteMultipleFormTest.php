<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\feeds\Entity\Feed;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Tests the delete multiple confirmation form.
 *
 * @group feeds
 */
class DeleteMultipleFormTest extends FeedsBrowserTestBase {

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

    // Create two feeds.
    $this->feed1 = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $this->feed2 = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
  }

  /**
   * Tests the feed delete form as admin.
   */
  public function testWithAdminPrivileges() {
    // Add the selection to the tempstore just like DeleteFeedAction would.
    $selection[$this->feed1->id()] = $this->feed1->id();
    $selection[$this->feed2->id()] = $this->feed2->id();
    $tempstore = $this->container->get('tempstore.private')->get('feeds_feed_multiple_delete_confirm');
    $tempstore->set($this->adminUser->id() . ':feeds_feed', $selection);

    $this->drupalGet('/admin/content/feed/delete');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', 'h1', 'Are you sure you want to delete these feeds?');
    $assert->pageTextContains($this->feed1->label());
    $assert->pageTextContains($this->feed2->label());
    $this->submitForm([], 'Delete');
    $assert = $this->assertSession();
    $assert->addressEquals('/admin/content/feed');
    $assert->pageTextContains('Deleted 2 feeds.');

    // Check that both feeds no longer exist.
    $this->assertNull(Feed::load(1));
    $this->assertNull(Feed::load(2));

    // Assert that the tempstore is now empty.
    $this->assertNull($tempstore->get($this->adminUser->id() . ':feeds_feed'));
  }

  /**
   * Tests the feed delete form with limited privileges.
   *
   * The logged in user may only delete feed 1, not feed 2.
   */
  public function testWithLimitedPrivileges() {
    // Create a user who may only delete feed 1.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'delete own ' . $this->feedType->id() . ' feeds',
      'view ' . $this->feedType->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Set owner of feed 1.
    $this->feed1->uid = $account->id();
    $this->feed1->save();

    // Add the selection to the tempstore just like DeleteFeedAction would.
    $selection[$this->feed1->id()] = $this->feed1->id();
    $selection[$this->feed2->id()] = $this->feed2->id();
    $tempstore = $this->container->get('tempstore.private')->get('feeds_feed_multiple_delete_confirm');
    $tempstore->set($account->id() . ':feeds_feed', $selection);

    $this->drupalGet('/admin/content/feed/delete');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', 'h1', 'Are you sure you want to delete these feeds?');
    $assert->pageTextContains($this->feed1->label());
    $assert->pageTextContains($this->feed2->label());
    $this->submitForm([], 'Delete');
    $assert = $this->assertSession();
    $assert->addressEquals('/admin/content/feed');
    $assert->pageTextContains('Deleted 1 feed.');
    $assert->pageTextContains('1 feed has not been deleted because you do not have the necessary permissions.');

    // Check that feed 1 no longer exist, but feed 2 still does.
    $this->assertNull(Feed::load(1));
    $this->assertInstanceOf(Feed::class, Feed::load(2));

    // Assert that the tempstore is now empty.
    $this->assertNull($tempstore->get($account->id() . ':feeds_feed'));
  }

}
