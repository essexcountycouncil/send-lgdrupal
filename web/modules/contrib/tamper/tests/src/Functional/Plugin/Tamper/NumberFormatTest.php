<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the number format plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\NumberFormat
 * @group tamper
 */
class NumberFormatTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'number_format';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'decimals' => 0,
          'dec_point' => '.',
          'thousands_sep' => ',',
        ],
      ],
      'with values' => [
        'expected' => [
          'decimals' => 2,
          'dec_point' => ',',
          'thousands_sep' => '.',
        ],
        'edit' => [
          'decimals' => 2,
          'dec_point' => ',',
          'thousands_sep' => '.',
        ],
      ],
    ];
  }

}
