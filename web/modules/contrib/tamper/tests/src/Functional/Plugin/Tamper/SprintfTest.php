<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the sprintf plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Sprintf
 * @group tamper
 */
class SprintfTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'sprintf';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'format' => '%s',
        ],
      ],
      'with values' => [
        'expected' => [
          'format' => '%08d',
        ],
        'edit' => [
          'format' => '%08d',
        ],
      ],
    ];
  }

}
