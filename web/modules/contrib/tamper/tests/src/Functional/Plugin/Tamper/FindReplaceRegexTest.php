<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the find and replace regex plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\FindReplaceRegex
 * @group tamper
 */
class FindReplaceRegexTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'find_replace_regex';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'invalid values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Invalid regular expression.',
        ],
      ],
      'only find value' => [
        'expected' => [
          'find' => '/cat/',
          'replace' => '',
          'limit' => NULL,
        ],
        'edit' => [
          'find' => '/cat/',
        ],
      ],
      'invalid expression' => [
        'expected' => [],
        'edit' => [
          'find' => 'foo',
        ],
        'errors' => [
          'Invalid regular expression.',
        ],
      ],
      'with values' => [
        'expected' => [
          'find' => '/cat\n/',
          'replace' => 'dog',
          'limit' => 4,
        ],
        'edit' => [
          'find' => '/cat\n/',
          'replace' => 'dog',
          'limit' => '4',
        ],
      ],
    ];
  }

}
