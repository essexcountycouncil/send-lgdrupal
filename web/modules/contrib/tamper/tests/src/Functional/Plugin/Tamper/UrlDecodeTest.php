<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the url decode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\UrlDecode
 * @group tamper
 */
class UrlDecodeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'url_decode';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'method' => 'rawurldecode',
        ],
      ],
      'with values' => [
        'expected' => [
          'method' => 'urldecode',
        ],
        'edit' => [
          'method' => 'urldecode',
        ],
      ],
    ];
  }

}
