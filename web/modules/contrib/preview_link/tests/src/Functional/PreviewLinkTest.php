<?php

namespace Drupal\Tests\preview_link\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestRevPub;
use Drupal\node\NodeInterface;
use Drupal\preview_link\PreviewLinkStorageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Integration test for the preview link.
 *
 * @group preview_link
 */
class PreviewLinkTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'preview_link',
    'node',
    'filter',
    'entity_test',
    'preview_link_test',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * The test node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin = $this->createUser(['generate preview links']);
    $this->createContentType(['type' => 'page']);
    $this->node = $this->createNode(['status' => NodeInterface::NOT_PUBLISHED]);

    \Drupal::configFactory()
      ->getEditable('preview_link.settings')
      ->set('enabled_entity_types', [
        'node' => ['page'],
        'entity_test_revpub' => ['entity_test_revpub'],
      ])
      ->save();
  }

  /**
   * Test the preview link page.
   */
  public function testPreviewLinkPage() {
    /** @var \Drupal\preview_link_test\TimeMachine $timeMachine */
    $timeMachine = \Drupal::service('datetime.time');
    $timeMachine->setTime(new \DateTime('14 May 2014 14:00:00'));

    $assert = $this->assertSession();
    // Can only be visited by users with correct permission.
    $url = Url::fromRoute('entity.node.generate_preview_link', [
      'node' => $this->node->id(),
    ]);
    $this->drupalGet($url);
    $assert->statusCodeEquals(403);

    $this->drupalLogin($this->admin);
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);

    // Grab the link from the page and ensure it works.
    $link = $this->cssSelect('.preview-link__link')[0]->getText();
    $this->assertSession()->pageTextContains('Expiry: 1 week');
    $this->drupalGet($link);
    $assert->statusCodeEquals(200);
    $assert->responseContains($this->node->getTitle());

    // Submitting form re-generates the link.
    $this->drupalPostForm($url, [], 'Regenerate preview link');
    $new_link = $this->cssSelect('.preview-link__link')[0]->getText();
    $this->assertNotEquals($link, $new_link);

    // Old link doesn't work.
    $this->drupalGet($link);
    $assert->statusCodeEquals(403);
    $assert->responseNotContains($this->node->getTitle());

    // New link does work.
    $this->drupalGet($new_link);
    $assert->statusCodeEquals(200);
    $assert->responseContains($this->node->getTitle());

    // Logout, new link works for anonymous user.
    $this->drupalLogout();
    $this->drupalGet($new_link);
    $assert->statusCodeEquals(200);
    $assert->responseContains($this->node->getTitle());
  }

  /**
   * Test preview link reset.
   */
  public function testReset() {
    /** @var \Drupal\preview_link_test\TimeMachine $timeMachine */
    $timeMachine = \Drupal::service('datetime.time');
    $currentTime = new \DateTime('14 May 2014 14:00:00');
    $timeMachine->setTime($currentTime);

    $this->drupalLogin($this->createUser(['generate preview links']));
    $entity = EntityTestRevPub::create();
    $entity->save();

    $previewLinkStorage = \Drupal::entityTypeManager()->getStorage('preview_link');
    assert($previewLinkStorage instanceof PreviewLinkStorageInterface);
    $previewLink = $previewLinkStorage->createPreviewLinkForEntity($entity);
    $token = $previewLink->getToken();
    $previewLink->save();
    $this->assertEquals($currentTime->getTimestamp(), $previewLink->getGeneratedTimestamp());

    $url = Url::fromRoute('entity.entity_test_revpub.generate_preview_link', ['entity_test_revpub' => $entity->id()]);
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains('Generate a preview link for the entity.');
    $currentTime = new \DateTime('14 May 2014 20:00:00');
    $timeMachine->setTime($currentTime);
    $this->drupalPostForm(NULL, [], 'Reset lifetime');
    $this->assertSession()->pageTextContains('Preview link will now expire at Wed, 05/21/2014 - 20:00.');

    // Reload preview link.
    $previewLink = $previewLinkStorage->getPreviewLinkForEntity($entity);
    $this->assertEquals($currentTime->getTimestamp(), $previewLink->getGeneratedTimestamp());
    // Ensure token was not regenerated.
    $this->assertEquals($token, $previewLink->getToken());
  }

}
