<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the url encode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\UrlEncode
 * @group tamper
 */
class UrlEncodeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'url_encode';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'method' => 'rawurlencode',
        ],
      ],
      'with values' => [
        'expected' => [
          'method' => 'urlencode',
        ],
        'edit' => [
          'method' => 'urlencode',
        ],
      ],
    ];
  }

}
