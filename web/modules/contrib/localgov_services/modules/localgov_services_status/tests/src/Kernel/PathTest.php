<?php

namespace Drupal\Tests\localgov_services_status\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests path alias for status maintained with landing pages.
 *
 * @coversDefaultClass \Drupal\localgov_services_status\PathProcessor
 * @group localgov_services
 */
class PathTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'path',
    'path_alias',
    'link',
    'node',
    'options',
    'system',
    'text',
    'user',
    'condition_field',
    'localgov_services_status',
    'localgov_services_navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installConfig([
      'node',
      'localgov_services_navigation',
      'localgov_services_status',
    ]);

    $this->pathProcessor = $this->container->get('localgov_services_status.path_processor');
    $node = Node::create([
      'title' => 'Test Landing Page',
      'type' => 'localgov_services_landing',
      'path' => ['alias' => '/foo'],
    ]);
    $node->save();
  }

  /**
   * @covers ::processInbound
   */
  public function testProcessInbound() {
    $processed = $this->pathProcessor->processInbound('/foo/status', Request::create('/foo/status'));
    $this->assertEquals('/node/1/status', $processed);

    $processed = $this->pathProcessor->processInbound('/node/1/status', Request::create('/node/1/status'));
    $this->assertEquals('/node/1/status', $processed);

    $processed = $this->pathProcessor->processInbound('/bar/status', Request::create('/bar/status'));
    $this->assertEquals('/bar/status', $processed);

    $processed = $this->pathProcessor->processInbound('/node/2/status', Request::create('/node/2/status'));
    $this->assertEquals('/node/2/status', $processed);
  }

  /**
   * @covers ::processOutbound
   */
  public function testProcessOutbound() {
    $processed = $this->pathProcessor->processOutbound('/foo/status');
    $this->assertEquals('/foo/status', $processed);

    $processed = $this->pathProcessor->processOutbound('/node/1/status');
    $this->assertEquals('/foo/status', $processed);

    $processed = $this->pathProcessor->processOutbound('/bar/status');
    $this->assertEquals('/bar/status', $processed);

    $processed = $this->pathProcessor->processOutbound('/node/2/status');
    $this->assertEquals('/node/2/status', $processed);
  }

}
