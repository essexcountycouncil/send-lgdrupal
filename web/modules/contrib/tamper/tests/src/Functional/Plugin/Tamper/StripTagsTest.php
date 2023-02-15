<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the strip tags plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StripTags
 * @group tamper
 */
class StripTagsTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'strip_tags';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'allowed_tags' => '',
        ],
      ],
      'with values' => [
        'expected' => [
          'allowed_tags' => '<a><em>',
        ],
        'edit' => [
          'allowed_tags' => '<a><em>',
        ],
      ],
    ];
  }

}
