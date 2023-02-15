<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\TimeToDate;

/**
 * Tests the timetodate plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\TimeToDate
 * @group tamper
 */
class TimeToDateTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new TimeToDate([], 'timetodate', [], $this->getMockSourceDefinition());
  }

  /**
   * Test timetodate.
   */
  public function test() {
    $config = [
      'date_format' => "\I\\t'\s g \o'\c\l\o\c\k \J\i\m\.",
    ];
    $plugin = new TimeToDate($config, 'timetodate', [], $this->getMockSourceDefinition());

    $this->assertEquals("It's 7 o'clock Jim.", $plugin->tamper(mktime(7)));
  }

}
