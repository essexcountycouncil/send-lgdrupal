<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the rewrite plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Rewrite
 * @group tamper
 */
class RewriteTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'rewrite';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'text' => '',
        ],
      ],
      'with values' => [
        'expected' => [
          'text' => '[bar]',
        ],
        'edit' => [
          'text' => '[bar]',
        ],
      ],
    ];
  }

  /**
   * Tests if source keys instead of labels are displayed.
   */
  public function testDisplaySourceListKeys() {
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);
    $this->assertSession()->pageTextMatches('/\[foo\]/');
    $this->assertSession()->pageTextMatches('/\[bar\]/');
    $this->assertSession()->pageTextMatches('/\[baz\]/');
    $this->assertSession()->pageTextMatches('/\[quxxie\]/');
  }

}
