<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the convert case plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\ConvertCase
 * @group tamper
 */
class ConvertCaseTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'convert_case';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'operation' => 'ucfirst',
        ],
      ],
      'with values' => [
        'expected' => [
          'operation' => 'ucwords',
        ],
        'edit' => [
          'operation' => 'ucwords',
        ],
      ],
    ];
  }

}
