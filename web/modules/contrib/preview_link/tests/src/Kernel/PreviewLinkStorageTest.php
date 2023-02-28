<?php

namespace Drupal\Tests\preview_link\Kernel;

use Drupal\preview_link\Entity\PreviewLink;
use Drupal\preview_link\Entity\PreviewLinkInterface;

/**
 * Preview link form test.
 *
 * @group preview_link
 */
class PreviewLinkStorageTest extends PreviewLinkBase {

  /**
   * Testing node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The preview link storage.
   *
   * @var \Drupal\preview_link\PreviewLinkStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->node = $this->createNode();
    $this->storage = $this->container->get('entity_type.manager')->getStorage('preview_link');
  }

  /**
   * Ensure preview link creation works.
   */
  public function testCreatePreviewLink() {
    $preview_link = PreviewLink::create([
      'entity_type_id' => 'node',
      'entity_id' => $this->node->id(),
    ]);
    $this->assertIsString($preview_link->getToken());

    $preview_link = $this->storage->createPreviewLinkForEntity($this->node);
    $this->assertIsString($preview_link->getToken());

    $preview_link = $this->storage->createPreviewLink('node', $this->node->id());
    $this->assertIsString($preview_link->getToken());
  }

  /**
   * Test retrieving a preview link.
   */
  public function testGetPreviewLink() {
    $preview_link = $this->storage->createPreviewLinkForEntity($this->node);

    $retrieved_preview_link = $this->storage->getPreviewLinkForEntity($this->node);
    $this->assertPreviewLinkEqual($preview_link, $retrieved_preview_link);
  }

  /**
   * Ensure we can re-generate a token.
   */
  public function testRegenerateToken() {
    $preview_link = $this->storage->createPreviewLinkForEntity($this->node);
    $current_token = $preview_link->getToken();
    $current_timestamp = $preview_link->getGeneratedTimestamp();

    // Regenerate and ensure it changed.
    $preview_link->regenerateToken(TRUE);
    $preview_link->save();

    $this->assertNotEquals($current_token, $preview_link->getToken());
    $this->assertNotEquals($current_timestamp, $preview_link->getGeneratedTimestamp());
  }

  /**
   * Ensure two preview links are the same.
   *
   * @param \Drupal\preview_link\Entity\PreviewLinkInterface $preview_link1
   *   The first preview link.
   * @param \Drupal\preview_link\Entity\PreviewLinkInterface $preview_link2
   *   The second preview link.
   */
  protected function assertPreviewLinkEqual(PreviewLinkInterface $preview_link1, PreviewLinkInterface $preview_link2) {
    $this->assertEquals($preview_link1->getToken(), $preview_link2->getToken());
    $this->assertEquals($preview_link1->getUrl(), $preview_link2->getUrl());
  }

}
