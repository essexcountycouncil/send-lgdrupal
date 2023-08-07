<?php

namespace Drupal\Tests\search_api_location\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests RawTest plugin parsing.
 *
 * @group search_api_location
 * @coversDefaultClass \Drupal\search_api_location\Plugin\search_api_location\location_input\Raw
 */
class RawTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'search_api',
    'search_api_location',
  ];

  /**
   * The Raw location input plugin under test.
   *
   * @var \Drupal\search_api_location\Plugin\search_api_location\location_input\Raw
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->sut = $this->container
      ->get('plugin.manager.search_api_location.location_input')
      ->createInstance('raw');
  }

  /**
   * Test the parsed input entered by user in raw format.
   *
   * @covers ::getParsedInput
   * @dataProvider provideValidInput
   */
  public function testValidParsedInput($valid, $expected) {
    $input = ['value' => $valid];
    $parsedInput = $this->sut->getParsedInput($input);
    $this->assertEquals($parsedInput, $expected);
  }

  /**
   * Test the parsed input entered by user in raw format with invalid data.
   *
   * @covers ::getParsedInput
   */
  public function testInvalidInput() {
    $input = ['value' => '^20.548,67.945'];
    $this->assertNull($this->sut->getParsedInput($input));
  }

  /**
   * Tests with unexpected input.
   *
   * @covers ::getParsedInput
   */
  public function testWithUnexpectedInput() {
    $input = ['animal' => 'llama'];
    $this->expectException(\InvalidArgumentException::class);
    $this->sut->getParsedInput($input);
  }

  /**
   * Data provider for ::testValidParsedInput.
   */
  public function provideValidInput() {
    return [
      'simple' => ['20,67', '20,67'],
      'with decimals' => ['20.548,67.945', '20.548,67.945'],
      'with spaces' => ['  20.548,67.945', '20.548,67.945'],
    ];
  }

}
