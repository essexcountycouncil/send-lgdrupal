<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the explode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Explode
 * @group tamper
 */
class ExplodeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'explode';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'separator' => ',',
          'limit' => NULL,
        ],
      ],
      'with values' => [
        'expected' => [
          'separator' => '|',
          'limit' => 6,
        ],
        'edit' => [
          'separator' => '|',
          'limit' => '6',
        ],
      ],
    ];
  }

}
