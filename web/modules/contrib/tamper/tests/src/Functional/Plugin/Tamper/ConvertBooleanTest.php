<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the convert boolean plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\ConvertBoolean
 * @group tamper
 */
class ConvertBooleanTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'convert_boolean';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'true_value' => 'true',
          'false_value' => 'false',
          'match_case' => FALSE,
          'no_match_value' => FALSE,
        ],
      ],
      'with values' => [
        'expected' => [
          'true_value' => '1',
          'false_value' => '0',
          'match_case' => TRUE,
          'no_match_value' => FALSE,
        ],
        'edit' => [
          'true_value' => 1,
          'false_value' => 0,
          'match_case' => 1,
        ],
      ],
      'true value' => [
        'expected' => [
          'true_value' => 'true',
          'false_value' => 'false',
          'match_case' => FALSE,
          'no_match_value' => TRUE,
        ],
        'edit' => [
          'no_match_value' => 'true',
        ],
      ],
      'false value' => [
        'expected' => [
          'true_value' => 'true',
          'false_value' => 'false',
          'match_case' => FALSE,
          'no_match_value' => FALSE,
        ],
        'edit' => [
          'no_match_value' => 'false',
        ],
      ],
      'null value' => [
        'expected' => [
          'true_value' => 'true',
          'false_value' => 'false',
          'match_case' => FALSE,
          'no_match_value' => NULL,
        ],
        'edit' => [
          'no_match_value' => 'null',
        ],
      ],
      'do not modify value' => [
        'expected' => [
          'true_value' => 'true',
          'false_value' => 'false',
          'match_case' => FALSE,
          'no_match_value' => 'pass',
        ],
        'edit' => [
          'no_match_value' => 'pass',
        ],
      ],
      'other text' => [
        'expected' => [
          'true_value' => 'true',
          'false_value' => 'false',
          'match_case' => FALSE,
          'no_match_value' => 'my other text',
        ],
        'edit' => [
          'no_match_value' => 'other',
          'other_text' => 'my other text',
        ],
      ],
    ];
  }

}
