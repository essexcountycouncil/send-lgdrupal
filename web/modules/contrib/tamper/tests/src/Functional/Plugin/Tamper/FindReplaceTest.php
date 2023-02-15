<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the find and replace plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\FindReplace
 * @group tamper
 */
class FindReplaceTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'find_replace';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'find' => '',
          'replace' => '',
          'case_sensitive' => FALSE,
          'word_boundaries' => FALSE,
          'whole' => FALSE,
        ],
      ],
      'with values' => [
        'expected' => [
          'find' => 'Dog',
          'replace' => 'Cat',
          'case_sensitive' => TRUE,
          'word_boundaries' => TRUE,
          'whole' => TRUE,
        ],
        'edit' => [
          'find' => 'Dog',
          'replace' => 'Cat',
          'case_sensitive' => 1,
          'word_boundaries' => 1,
          'whole' => 1,
        ],
      ],
    ];
  }

}
