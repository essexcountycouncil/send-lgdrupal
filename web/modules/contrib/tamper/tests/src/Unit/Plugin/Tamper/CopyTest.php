<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\Copy;
use Drupal\tamper\TamperItem;

/**
 * Tests the copy plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Copy
 * @group tamper
 */
class CopyTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Copy([], 'copy', [], $this->getMockSourceDefinition());
  }

  /**
   * Get a tamper item to use in the test.
   *
   * @return \Drupal\tamper\TamperItem
   *   The tamperable item to use in the test.
   */
  protected function getTamperItem() {
    $item = new TamperItem();
    $item->setSourceProperty('title', 'Robots are cool.');
    $item->setSourceProperty('body', 'Robots are scary!');

    return $item;
  }

  /**
   * Test copy to.
   */
  public function testCopyTo() {
    $config = [
      Copy::SETTING_TO_FROM => 'to',
      Copy::SETTING_SOURCE => 'title',
    ];
    $expected = [
      'title' => 'Robots are scary!',
      'body' => 'Robots are scary!',
    ];

    $plugin = new Copy($config, 'copy', [], $this->getMockSourceDefinition());
    $item = $this->getTamperItem();

    $this->assertEquals('Robots are scary!', $plugin->tamper('Robots are scary!', $item));
  }

  /**
   * Test copy from.
   */
  public function testCopyFrom() {
    $config = [
      Copy::SETTING_TO_FROM => 'from',
      Copy::SETTING_SOURCE => 'title',
    ];

    $plugin = new Copy($config, 'copy', [], $this->getMockSourceDefinition());
    $item = $this->getTamperItem();

    $this->assertEquals('Robots are cool.', $plugin->tamper('Robots are scary!', $item));
  }

}
