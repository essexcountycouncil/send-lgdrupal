<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the trim plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Trim
 * @group tamper
 */
class TrimTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'trim';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'character' => '',
          'side' => 'trim',
        ],
      ],
      'with values' => [
        'expected' => [
          'character' => '$',
          'side' => 'ltrim',
        ],
        'edit' => [
          'character' => '$',
          'side' => 'ltrim',
        ],
      ],
    ];
  }

}
