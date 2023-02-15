<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the copy plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Copy
 * @group tamper
 */
class CopyTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'copy';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'to_from' => 'to',
          'copy_source' => NULL,
        ],
      ],
      'with values' => [
        'expected' => [
          'to_from' => 'from',
          'copy_source' => 'baz',
        ],
        'edit' => [
          'to_from' => 'from',
          'copy_source' => 'baz',
        ],
      ],
    ];
  }

}
