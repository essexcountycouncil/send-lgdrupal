<?php

namespace Drupal\Tests\search_api_location\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests generation of LocationDataType plugin.
 *
 * @group search_api_location
 */
class LocationDataTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'search_api',
    'search_api_location',
  ];

  /**
   * The location data type plugin under test.
   *
   * @var \Drupal\search_api_location\Plugin\search_api\data_type\LocationDataType
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->sut = $this->container->get('plugin.manager.search_api.data_type')
      ->createInstance('location');
  }

  /**
   * Test the GetValue method.
   */
  public function testGetValue() {
    $this->assertEquals($this->sut->getValue('POLYGON((1 1,5 1,5 5,1 5,1 1),(2 2,2 3,3 3,3 2,2 2))'), "3,3");
  }

  /**
   * Test the getFallbackType method.
   */
  public function testGetFallbackType() {
    $this->assertEquals(NULL, $this->sut->getFallbackType());
  }

}
