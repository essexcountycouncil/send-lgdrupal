<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the default value plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\DefaultValue
 * @group tamper
 */
class DefaultValueTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'default_value';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'default_value' => '',
          'only_if_empty' => FALSE,
        ],
      ],
      'with values' => [
        'expected' => [
          'default_value' => 'A default',
          'only_if_empty' => TRUE,
        ],
        'edit' => [
          'default_value' => 'A default',
          'only_if_empty' => 1,
        ],
      ],
    ];
  }

}
