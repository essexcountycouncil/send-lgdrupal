<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the strpos plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrPos
 * @group tamper
 */
class StrPosTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'str_pos';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'substring' => '',
        ],
      ],
      'with values' => [
        'expected' => [
          'substring' => 'cat',
        ],
        'edit' => [
          'substring' => 'cat',
        ],
      ],
    ];
  }

}
