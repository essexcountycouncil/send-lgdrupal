<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Test the required plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Required
 * @group tamper
 */
class RequiredTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'required';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'invert' => FALSE,
        ],
      ],
      'with values' => [
        'expected' => [
          'invert' => TRUE,
        ],
        'edit' => [
          'invert' => 1,
        ],
      ],
    ];
  }

}
