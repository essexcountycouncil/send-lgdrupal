<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Tests the import multiple confirmation form.
 *
 * @group feeds
 */
class ImportMultipleFormTest extends FeedsBrowserTestBase {

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
   * Tests the feed import form as admin.
   */
  public function testWithAdminPrivileges() {
    // Add the selection to the tempstore just like ImportFeedAction would.
    $selection[$this->feed1->id()] = $this->feed1->id();
    $selection[$this->feed2->id()] = $this->feed2->id();
    $tempstore = $this->container->get('tempstore.private')->get('feeds_feed_multiple_import_confirm');
    $tempstore->set($this->adminUser->id() . ':feeds_feed', $selection);

    $this->drupalGet('/admin/content/feed/import');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', 'h1', 'Are you sure you want to import the selected feeds?');
    $assert->pageTextContains($this->feed1->label());
    $assert->pageTextContains($this->feed2->label());
    $this->submitForm([], 'Import');
    $assert = $this->assertSession();
    $assert->addressEquals('/admin/content/feed');
    $assert->pageTextContains('Imported 2 feeds.');
    $assert->pageTextContains($this->feed1->label() . ': Created 25 Article items.');
    $assert->pageTextContains($this->feed2->label() . ': Created 6 Article items.');
    $this->assertNodeCount(31);

    // Assert that the tempstore is now empty.
    $this->assertNull($tempstore->get($this->adminUser->id() . ':feeds_feed'));
  }

  /**
   * Tests the feed import form with limited privileges.
   *
   * The logged in user may only import items from feed 1, not feed 2.
   */
  public function testWithLimitedPrivileges() {
    // Create a user who may only import items for feed 1.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'import own ' . $this->feedType->id() . ' feeds',
      'view ' . $this->feedType->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Set owner of feed 1.
    $this->feed1->uid = $account->id();
    $this->feed1->save();

    // Add the selection to the tempstore just like importFeedAction would.
    $selection[$this->feed1->id()] = $this->feed1->id();
    $selection[$this->feed2->id()] = $this->feed2->id();
    $tempstore = $this->container->get('tempstore.private')->get('feeds_feed_multiple_import_confirm');
    $tempstore->set($account->id() . ':feeds_feed', $selection);

    $this->drupalGet('/admin/content/feed/import');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', 'h1', 'Are you sure you want to import the selected feeds?');
    $assert->pageTextContains($this->feed1->label());
    $assert->pageTextContains($this->feed2->label());
    $this->submitForm([], 'Import');
    $assert = $this->assertSession();
    $assert->addressEquals('/admin/content/feed');
    $assert->pageTextContains('Imported 1 feed.');
    $assert->pageTextContains($this->feed1->label() . ': Created 25 Article items.');
    $assert->pageTextNotContains($this->feed2->label() . ': Created 6 Article items.');
    $assert->pageTextContains('1 feed has not been imported because you do not have the necessary permissions.');
    $this->assertNodeCount(25);

    // Assert that the tempstore is now empty.
    $this->assertNull($tempstore->get($account->id() . ':feeds_feed'));
  }

}
