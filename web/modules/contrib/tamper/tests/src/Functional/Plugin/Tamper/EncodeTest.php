<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the encode / decode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Encode
 *
 * @group tamper
 */
class EncodeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'encode';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'mode' => 'serialize',
        ],
      ],
      'with values' => [
        'expected' => [
          'mode' => 'json_decode',
        ],
        'edit' => [
          'mode' => 'json_decode',
        ],
      ],
    ];
  }

}
