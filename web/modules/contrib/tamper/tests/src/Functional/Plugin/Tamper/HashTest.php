<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the hash plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Hash
 * @group tamper
 */
class HashTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'hash';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'override' => FALSE,
        ],
      ],
      'with values' => [
        'expected' => [
          'override' => TRUE,
        ],
        'edit' => [
          'override' => '1',
        ],
      ],
    ];
  }

}
