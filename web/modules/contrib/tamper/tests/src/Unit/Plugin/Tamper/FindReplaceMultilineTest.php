<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\FindReplaceMultiline;

/**
 * Tests the multiline find and replace plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\FindReplaceMultiline
 * @group tamper
 */
class FindReplaceMultilineTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      FindReplaceMultiline::SETTING_FIND_REPLACE => [],
      FindReplaceMultiline::SETTING_SEPARATOR => '|',
      FindReplaceMultiline::SETTING_CASE_SENSITIVE => FALSE,
      FindReplaceMultiline::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplaceMultiline::SETTING_WHOLE => FALSE,
    ];
    return new FindReplaceMultiline($config, 'find_replace_multiline', [], $this->getMockSourceDefinition());
  }

  /**
   * Test the plugin with a single value.
   */
  public function testSingleValue() {
    $config = [
      FindReplaceMultiline::SETTING_FIND_REPLACE => ['cat|dog'],
      FindReplaceMultiline::SETTING_SEPARATOR => '|',
      FindReplaceMultiline::SETTING_CASE_SENSITIVE => FALSE,
      FindReplaceMultiline::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplaceMultiline::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplaceMultiline($config, 'find_replace_multiline', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The Cat went to the park.'));
    $this->assertEquals('The dogwent to the park.', $plugin->tamper('The Catwent to the park.'));
  }

  /**
   * Test the plugin with a single value.
   */
  public function testSingleValues() {
    $config = [
      FindReplaceMultiline::SETTING_FIND_REPLACE => [
        'cat|dog',
        'orange|mango',
      ],
      FindReplaceMultiline::SETTING_SEPARATOR => '|',
      FindReplaceMultiline::SETTING_CASE_SENSITIVE => FALSE,
      FindReplaceMultiline::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplaceMultiline::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplaceMultiline($config, 'find_replace_multiline', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog ate the mango.', $plugin->tamper('The cat ate the orange.'));
    $this->assertEquals('The mango was eaten by the dog.', $plugin->tamper('The orange was eaten by the cat.'));
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('The mango is the best fruit.', $plugin->tamper('The orange is the best fruit.'));
  }

  /**
   * Tests with missing separator.
   */
  public function testWithMissingSeparator() {
    $config = [
      FindReplaceMultiline::SETTING_FIND_REPLACE => ['cat/dog'],
      FindReplaceMultiline::SETTING_SEPARATOR => ';',
      FindReplaceMultiline::SETTING_CASE_SENSITIVE => FALSE,
      FindReplaceMultiline::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplaceMultiline::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplaceMultiline($config, 'find_replace_multiline', [], $this->getMockSourceDefinition());
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('In the configuration the string separator ";" is missing.');
    $plugin->tamper('The cat ate the orange.');
  }

  /**
   * Test the plugin as case sensitive.
   */
  public function testSingleValueCaseSensitive() {
    $config = [
      FindReplaceMultiline::SETTING_FIND_REPLACE => ['cat|dog'],
      FindReplaceMultiline::SETTING_SEPARATOR => '|',
      FindReplaceMultiline::SETTING_CASE_SENSITIVE => TRUE,
      FindReplaceMultiline::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplaceMultiline::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplaceMultiline($config, 'find_replace_multiline', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('The Cat went to the park.', $plugin->tamper('The Cat went to the park.'));
    $this->assertEquals('The dogwent to the park.', $plugin->tamper('The catwent to the park.'));
  }

  /**
   * Test the plugin as respecting word boundaries.
   */
  public function testSingleValueWordBoundaries() {
    $config = [
      FindReplaceMultiline::SETTING_FIND_REPLACE => ['cat|dog'],
      FindReplaceMultiline::SETTING_SEPARATOR => '|',
      FindReplaceMultiline::SETTING_CASE_SENSITIVE => FALSE,
      FindReplaceMultiline::SETTING_WORD_BOUNDARIES => TRUE,
      FindReplaceMultiline::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplaceMultiline($config, 'find_replace_multiline', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The Cat went to the park.'));
    $this->assertEquals('The catwent to the park.', $plugin->tamper('The catwent to the park.'));
  }

  /**
   * Test the plugin as replace whole words only.
   */
  public function testSingleValueWhole() {
    $config = [
      FindReplaceMultiline::SETTING_FIND_REPLACE => ['cat|dog'],
      FindReplaceMultiline::SETTING_SEPARATOR => '|',
      FindReplaceMultiline::SETTING_CASE_SENSITIVE => FALSE,
      FindReplaceMultiline::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplaceMultiline::SETTING_WHOLE => TRUE,
    ];
    $plugin = new FindReplaceMultiline($config, 'find_replace_multiline', [], $this->getMockSourceDefinition());
    $this->assertEquals('The cat went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('dog', $plugin->tamper('cat'));
    $this->assertEquals('dog', $plugin->tamper('Cat'));
  }

  /**
   * Test the plugin with a multiple values.
   */
  public function testMultipleValues() {
    $plugin = new FindReplaceMultiline([], 'find_replace_multiline', [], $this->getMockSourceDefinition());
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $plugin->tamper(['foo', 'bar', 'baz']);
  }

}
