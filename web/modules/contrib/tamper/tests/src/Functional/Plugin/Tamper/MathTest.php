<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the math plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Math
 * @group tamper
 */
class MathTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'math';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Value field is required.',
        ],
      ],
      'only required values' => [
        'expected' => [
          'operation' => 'addition',
          'flip' => FALSE,
          'value' => 8.0,
        ],
        'edit' => [
          'value' => '8',
        ],
      ],
      'with values' => [
        'expected' => [
          'operation' => 'multiplication',
          'flip' => TRUE,
          'value' => 3.0,
        ],
        'edit' => [
          'operation' => 'multiplication',
          'flip' => '1',
          'value' => '3',
        ],
      ],
    ];
  }

}
