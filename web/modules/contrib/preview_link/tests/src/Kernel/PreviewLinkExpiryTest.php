<?php

namespace Drupal\Tests\preview_link\Kernel;

use Drupal\preview_link\Entity\PreviewLink;

/**
 * Preview link expiry test.
 *
 * @group preview_link
 */
class PreviewLinkExpiryTest extends PreviewLinkBase {

  /**
   * Testing node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->node = $this->createNode();
  }

  /**
   * Test preview links are automatically expired on cron.
   */
  public function testPreviewLinkExpires() {
    $days = \Drupal::state()->get('preview_link_expiry_days', 7);
    // Add an extra day to make it expired.
    $days = $days + 1;
    $days_in_seconds = $days * 86400;
    $expired_preview_link = PreviewLink::create([
      'entity_type_id' => 'node',
      'entity_id' => $this->node->id(),
      // Set a timestamp that will definitely be expired.
      'generated_timestamp' => $days_in_seconds,
    ]);
    $expired_preview_link->save();
    $id = $expired_preview_link->id();

    // Run cron and then ensure the entity is gone.
    preview_link_cron();
    $this->assertNull($this->storage->load($id));
  }

}
