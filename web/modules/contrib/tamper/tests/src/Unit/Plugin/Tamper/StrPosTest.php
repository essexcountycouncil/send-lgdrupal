<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\StrPos;

/**
 * Tests the strpos plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrPos
 * @group tamper
 */
class StrPosTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StrPos([], 'strpos', [], $this->getMockSourceDefinition());
  }

  /**
   * Test string pos determination.
   *
   * @covers ::tamper
   */
  public function testStrPos() {
    $config = [
      'substring' => 'find me',
    ];
    $plugin = new StrPos($config, 'strpos', [], $this->getMockSourceDefinition());

    $this->assertEquals(25, $plugin->tamper('this string let the test find me easily'));
    $this->assertEquals(0, $plugin->tamper('find me right at the beginning'));
    $this->assertFalse($plugin->tamper('this one is missing the substring'));
  }

  /**
   * Test string pos determination with empty substring.
   *
   * @covers ::tamper
   */
  public function testStrPosEmpty() {
    $config = [
      'substring' => '',
    ];
    $plugin = new StrPos($config, 'strpos', [], $this->getMockSourceDefinition());

    $this->assertFalse($plugin->tamper('this string let the test find me easily'));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidInput() {
    $this->expectException(TamperException::class);
    $this->plugin->tamper(new \stdClass());
  }

}
