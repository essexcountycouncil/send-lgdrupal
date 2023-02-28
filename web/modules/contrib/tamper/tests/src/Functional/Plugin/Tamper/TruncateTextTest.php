<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the Truncate Text plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\TruncateText
 * @group tamper
 */
class TruncateTextTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'truncate_text';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'num_char' => 0,
          'ellipses' => FALSE,
          'wordsafe' => FALSE,
        ],
      ],
      'with values' => [
        'expected' => [
          'num_char' => 600,
          'ellipses' => TRUE,
          'wordsafe' => TRUE,
        ],
        'edit' => [
          'num_char' => '600',
          'ellipses' => '1',
          'wordsafe' => '1',
        ],
      ],
    ];
  }

}
