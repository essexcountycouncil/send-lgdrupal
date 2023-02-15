<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\StrLen;

/**
 * Tests the strlen plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrLen
 * @group tamper
 */
class StrLenTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StrLen([], 'strlen', [], $this->getMockSourceDefinition());
  }

  /**
   * Test string length determination.
   *
   * @covers ::tamper
   */
  public function testStrLen() {
    $this->assertEquals(15, $this->plugin->tamper('a simple string'));
    $this->assertEquals(47, $this->plugin->tamper('a string with special characters like äöü or è.'));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidInput() {
    $this->expectException(TamperException::class);
    $this->plugin->tamper(new \stdClass());
  }

}
