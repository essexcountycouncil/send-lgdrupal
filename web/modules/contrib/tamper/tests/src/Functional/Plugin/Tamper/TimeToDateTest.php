<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the timetodate plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\TimeToDate
 * @group tamper
 */
class TimeToDateTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'timetodate';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'date_format' => '',
        ],
      ],
      'with values' => [
        'expected' => [
          'date_format' => "\I\\t'\s g \o'\c\l\o\c\k \J\i\m\.",
        ],
        'edit' => [
          'date_format' => "\I\\t'\s g \o'\c\l\o\c\k \J\i\m\.",
        ],
      ],
    ];
  }

}
