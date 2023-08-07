<?php

namespace Drupal\Tests\search_api_location_geocoder\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Coordinates;

/**
 * Test for the geocode plugin.
 *
 * @group search_api_location
 * @coversDefaultClass \Drupal\search_api_location_geocoder\Plugin\search_api_location\location_input\Geocode
 */
class GeocodeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'search_api',
    'search_api_location',
    'search_api_location_geocoder',
    'geocoder',
  ];

  /**
   * The Geocode location input plugin under test.
   *
   * @var \Drupal\search_api_location_geocoder\Plugin\search_api_location\location_input\Geocode
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $ghent = new AddressCollection([new Address('n/a', new AdminLevelCollection([]), new Coordinates(51.037455, 3.7192784))]);

    // Mock the Geocoder service.
    $geocoder = $this->createMock('\Drupal\geocoder\Geocoder');

    $geocoder->expects($this->any())
      ->method('geocode')
      ->with('Ghent')
      ->willReturn($ghent);

    // Replace the geocoder service.
    $this->container->set('geocoder', $geocoder);
    \Drupal::setContainer($this->container);

    $configuration = [
      'plugins' => [
        'openstreetmap' => [
          'checked' => TRUE,
          'weight' => '-3',
        ],
        'llama' => [
          'checked' => FALSE,
          'weight' => '-3',
        ],
      ],
    ];
    $this->sut = $this->container
      ->get('plugin.manager.search_api_location.location_input')
      ->createInstance('geocode', $configuration);
  }

  /**
   * Test the parsing of input entered by user in text format.
   *
   * @covers ::getParsedInput
   */
  public function testGetParsedInput() {
    $input['value'] = 'Ghent';
    $parsed = $this->sut->getParsedInput($input);
    [$lat, $lng] = explode(',', $parsed);
    $this->assertEquals(round($lat, 0, PHP_ROUND_HALF_DOWN), 51);
    $this->assertEquals(round($lng, 0, PHP_ROUND_HALF_DOWN), 4);
  }

  /**
   * Tests with invalid input.
   *
   * @covers ::getParsedInput
   */
  public function testWithUnexpectedInput() {
    $input = ['animal' => 'llama'];
    $this->expectException(\InvalidArgumentException::class);
    $this->sut->getParsedInput($input);
  }

  /**
   * Tests with non array input.
   *
   * @covers ::getParsedInput
   */
  public function testWithNonArrayInput() {
    $input = ['llama'];
    $this->expectException(\InvalidArgumentException::class);
    $this->sut->getParsedInput($input);
  }

}
