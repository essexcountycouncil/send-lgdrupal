<?php

namespace Drupal\Tests\facets_map_widget\Unit\Plugin\widget;

use Drupal\facets_map_widget\Plugin\facets\widget\RptMapWidget;
use Drupal\Tests\facets\Unit\Plugin\widget\WidgetTestBase;

/**
 * Unit test for RptMapwidget.
 *
 * @group search_api_location
 */
class RptMapWidgetTest extends WidgetTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->widget = new RptMapWidget([], 'rpt_map_widget', []);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetQueryType() {
    $result = $this->widget->getQueryType();
    $this->assertEquals('search_api_rpt', $result);
  }

  /**
   * {@inheritdoc}
   */
  public function testDefaultConfiguration() {
    $default_config = $this->widget->defaultConfiguration();
    $expected = [
      'show_numbers' => FALSE,
    ];
    $this->assertEquals($expected, $default_config);
  }

  /**
   * {@inheritdoc}
   */
  public function testIsPropertyRequired() {
    $this->assertFalse($this->widget->isPropertyRequired('llama', 'owl'));
    $this->assertTrue($this->widget->isPropertyRequired('rpt', 'processors'));
  }

}
