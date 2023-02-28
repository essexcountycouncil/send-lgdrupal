<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the implode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Implode
 * @group tamper
 */
class ImplodeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'implode';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'glue' => ',',
        ],
      ],
      'with values' => [
        'expected' => [
          'glue' => '|',
        ],
        'edit' => [
          'glue' => '|',
        ],
      ],
      'special chars' => [
        'expected' => [
          'glue' => '%n',
        ],
        'edit' => [
          'glue' => '%n',
        ],
      ],
    ];
  }

}
