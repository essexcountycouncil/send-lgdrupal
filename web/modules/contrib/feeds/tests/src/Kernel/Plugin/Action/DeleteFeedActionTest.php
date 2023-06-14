<?php

namespace Drupal\Tests\feeds\Kernel\Plugin\Action;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\system\Entity\Action;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\feeds\Plugin\Action\DeleteFeedAction
 *
 * @group feeds
 */
class DeleteFeedActionTest extends FeedsKernelTestBase {

  /**
   * The user performing the action.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $this->testUser = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $this->testUser->save();
    $this->container->get('current_user')->setAccount($this->testUser);
  }

  /**
   * Tests applying the action plugin "feeds_feed_delete_action".
   */
  public function testDeleteFeedAction() {
    // Create a feed type.
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
    ]);

    // Create two feeds.
    $feed1 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $feed2 = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Apply the action plugin.
    $action = Action::create([
      'id' => 'foo',
      'plugin' => 'feeds_feed_delete_action',
    ]);
    $action->save();
    $action->execute([$feed1, $feed2]);

    /** @var \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store */
    $temp_store = $this->container->get('tempstore.private');
    $store_entries = $temp_store->get('feeds_feed_multiple_delete_confirm')->get($this->testUser->id() . ':feeds_feed');
    $expected = [
      $feed1->id() => $feed1->id(),
      $feed2->id() => $feed2->id(),
    ];
    $this->assertSame($expected, $store_entries);
  }

}
