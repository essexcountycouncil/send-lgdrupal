<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\Plugin\Tamper\SkipOnEmpty;

/**
 * Tests the skip_on_empty plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\SkipOnEmpty
 * @group tamper
 */
class SkipOnEmptyTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new SkipOnEmpty([], 'skip_on_empty', [], $this->getMockSourceDefinition());
  }

  /**
   * Test with a string.
   */
  public function testWithValue() {
    $this->assertEquals('Expected', $this->plugin->tamper('Expected'));
  }

  /**
   * Test with empty values.
   *
   * @dataProvider dataProviderEmptyValues
   */
  public function testWithEmptyValue($value) {
    $this->expectException(SkipTamperDataException::class);
    $this->plugin->tamper($value);
  }

  /**
   * Data provider for ::testWithEmptyValue().
   */
  public function dataProviderEmptyValues() {
    return [
      // Empty string.
      [''],
      // Bool false.
      [FALSE],
      // Null.
      [NULL],
      // Empty array.
      [[]],
      // Integer.
      [0],
    ];
  }

}
