<?php

namespace Drupal\Tests\preview_link\Kernel;

use Drupal\preview_link\Entity\PreviewLink;

/**
 * Test preview link access.
 *
 * @group preview_link
 */
class PreviewLinkAccessTest extends PreviewLinkBase {

  /**
   * Node for testing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Preview link for node 1.
   *
   * @var \Drupal\preview_link\Entity\PreviewLinkInterface
   */
  protected $previewLink;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->node = $this->createNode();
    $this->previewLink = PreviewLink::create([
      'entity_type_id' => 'node',
      'entity_id' => $this->node->id(),
    ]);
    $this->previewLink->save();
  }

  /**
   * Test the preview access service.
   *
   * @dataProvider previewAccessDeniedDataProvider
   */
  public function testPreviewAccessDenied($entity_type_id, $entity_id, $token, $expected_result) {
    $entity = $this->container->get('entity_type.manager')->getStorage($entity_type_id)->load($entity_id);
    $access = $this->container->get('access_check.preview_link')->access($entity, $token);
    $this->assertEquals($expected_result, $access->isAllowed());
  }

  /**
   * Data provider for testPreviewAccess().
   */
  public function previewAccessDeniedDataProvider() {
    return [
      'empty token' => ['node', 1, '', FALSE],
      'invalid token' => ['node', 1, 'invalid 123', FALSE],
      'invalid entity id' => ['node', 99, 'correct-token', FALSE],
    ];
  }

  /**
   * Ensure access is allowed with a valid token.
   */
  public function testPreviewAccessAllowed() {
    $access = $this->container->get('access_check.preview_link')->access($this->node, $this->previewLink->getToken());
    $this->assertEquals(TRUE, $access->isAllowed());
  }

}
