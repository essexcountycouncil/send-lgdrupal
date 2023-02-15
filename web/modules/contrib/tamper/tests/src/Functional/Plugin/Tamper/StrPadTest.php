<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the StrPad plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrPad
 * @group tamper
 */
class StrPadTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'str_pad';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'pad_length' => 10,
          'pad_string' => ' ',
          'pad_type' => STR_PAD_RIGHT,
        ],
      ],
      'with values' => [
        'expected' => [
          'pad_length' => 12,
          'pad_string' => '0',
          'pad_type' => STR_PAD_BOTH,
        ],
        'edit' => [
          'pad_length' => '12',
          'pad_string' => '0',
          'pad_type' => STR_PAD_BOTH,
        ],
      ],
    ];
  }

}
