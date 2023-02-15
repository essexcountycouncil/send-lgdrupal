<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the find and replace (multiline) plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\FindReplaceMultiline
 * @group tamper
 */
class FindReplaceMultilineTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'find_replace_multiline';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Text to find and the replacements field is required',
        ],
      ],
      'minimal values' => [
        'expected' => [
          'find_replace' => ['cat|dog'],
          'separator' => '|',
          'case_sensitive' => FALSE,
          'word_boundaries' => FALSE,
          'whole' => FALSE,
        ],
        'edit' => [
          'find_replace' => 'cat|dog',
        ],
      ],
      'check remove empty lines' => [
        'expected' => [
          'find_replace' => [
            'cat|dog ',
            ' Foo|Bar',
          ],
          'separator' => '|',
          'case_sensitive' => FALSE,
          'word_boundaries' => FALSE,
          'whole' => FALSE,
        ],
        'edit' => [
          'find_replace' => "\ncat|dog \n \n Foo|Bar\n\n",
        ],
      ],
      'missing separator' => [
        'expected' => [],
        'edit' => [
          'find_replace' => "John|Paul\ncat/dog\n",
        ],
        'errors' => [
          'Line 2 is missing the separator "|".',
        ],
      ],
      'missing separator, other separator' => [
        'expected' => [],
        'edit' => [
          'find_replace' => "John|Paul",
          'separator' => ',',
        ],
        'errors' => [
          'Line 1 is missing the separator ",".',
        ],
      ],
      'two lines missing separator' => [
        'expected' => [],
        'edit' => [
          'find_replace' => "John|Paul\nCat|Dog",
          'separator' => ',',
        ],
        'errors' => [
          'Lines 1 and 2 are missing the separator ",".',
        ],
      ],
      'multiple lines missing separator' => [
        'expected' => [],
        'edit' => [
          'find_replace' => "John|Paul\nCat|Dog\nFoo|Bar\nHello|Goodbye",
          'separator' => ',',
        ],
        'errors' => [
          'Lines 1, 2, 3 and 4 are missing the separator ",".',
        ],
      ],
      'with values' => [
        'expected' => [
          'find_replace' => [
            'John,Paul',
            'Cat,Dog',
          ],
          'separator' => ',',
          'case_sensitive' => TRUE,
          'word_boundaries' => TRUE,
          'whole' => TRUE,
        ],
        'edit' => [
          'find_replace' => "John,Paul\nCat,Dog",
          'separator' => ',',
          'case_sensitive' => 1,
          'word_boundaries' => 1,
          'whole' => 1,
        ],
      ],
    ];
  }

}
