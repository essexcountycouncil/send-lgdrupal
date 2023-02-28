<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the keyword filter plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\KeywordFilter
 * @group tamper
 */
class KeywordFilterTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'keyword_filter';

  /**
   * {@inheritdoc}
   */
  public function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'words' => '',
          'word_boundaries' => FALSE,
          'exact' => FALSE,
          'case_sensitive' => FALSE,
          'invert' => FALSE,
          'word_list' => [],
          'regex' => FALSE,
          'function' => 'mb_stripos',
        ],
      ],
      'with values' => [
        'expected' => [
          'words' => "[Foo]\nBar",
          'word_boundaries' => TRUE,
          'exact' => TRUE,
          'case_sensitive' => TRUE,
          'invert' => TRUE,
          'word_list' => [
            '/^\[Foo\]$/u',
            '/^Bar$/u',
          ],
          'regex' => TRUE,
          'function' => 'matchRegex',
        ],
        'edit' => [
          'words' => "[Foo]\nBar",
          'word_boundaries' => '1',
          'exact' => '1',
          'case_sensitive' => '1',
          'invert' => '1',
        ],
      ],
      'word boundaries' => [
        'expected' => [
          'words' => "F[o]o\n_Bar_\n88x88",
          'word_boundaries' => TRUE,
          'exact' => FALSE,
          'case_sensitive' => FALSE,
          'invert' => FALSE,
          'word_list' => [
            '/\bF\[o\]o\b/ui',
            '/\b_Bar_\b/ui',
            '/\b88x88\b/ui',
          ],
          'regex' => TRUE,
          'function' => 'matchRegex',
        ],
        'edit' => [
          'words' => "F[o]o\n_Bar_\n88x88",
          'word_boundaries' => '1',
        ],
      ],
      'word boundaries error' => [
        'expected' => [],
        'edit' => [
          'words' => "F[o]o\n*Bar_\n88x88",
          'word_boundaries' => '1',
        ],
        'errors' => [
          'Search text must begin and end with a letter, number, or underscore to use the Respect word boundaries option.',
        ],
      ],
      'case_sensitive' => [
        'expected' => [
          'words' => 'Foo',
          'word_boundaries' => FALSE,
          'exact' => FALSE,
          'case_sensitive' => TRUE,
          'invert' => FALSE,
          'word_list' => [
            'Foo',
          ],
          'regex' => FALSE,
          'function' => 'mb_strpos',
        ],
        'edit' => [
          'words' => 'Foo',
          'case_sensitive' => '1',
        ],
      ],
    ];
  }

}
