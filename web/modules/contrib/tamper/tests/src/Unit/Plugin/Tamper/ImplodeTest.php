<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Implode;

/**
 * Tests the implode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Implode
 * @group tamper
 */
class ImplodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      Implode::SETTING_GLUE => ',',
    ];
    return new Implode($config, 'implode', [], $this->getMockSourceDefinition());
  }

  /**
   * Tests imploding with an object.
   */
  public function testImplodeWithObject() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be an array or a string.');
    $original = new \stdClass();
    $this->plugin->tamper($original);
  }

  /**
   * Tests imploding with a single value.
   */
  public function testImplodeWithSingleValue() {
    $original = 'foobar';
    $expected = 'foobar';
    $this->assertEquals($expected, $this->plugin->tamper($original));
  }

  /**
   * Tests imploding with multiple values.
   */
  public function testImplodeWithMultipleValues() {
    $original = ['foo', 'bar', 'baz'];
    $expected = 'foo,bar,baz';
    $this->assertEquals($expected, $this->plugin->tamper($original));
  }

}
