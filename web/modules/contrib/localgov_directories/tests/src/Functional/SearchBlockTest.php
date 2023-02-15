<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_directories\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Directory search block.
 *
 * Tests include:
 * - Prepopulated search field bug scenario.
 */
class SearchBlockTest extends BrowserTestBase {

  /**
   * Tests the search field.
   *
   * When a Directory channel page is loaded, the search field in the channel
   * search block should be empty.
   */
  public function testIsSearchFieldEmpty() :void {

    $dir_channel_node = $this->createNode([
      'title' => 'I am a directory channel page',
      'type'  => 'localgov_directory',
    ]);
    $dir_channel_node->save();
    $dir_channel_page_path = $dir_channel_node->toUrl()->toString();

    $this->drupalPlaceBlock('localgov_directories_channel_search_block', [
      'context_mapping' => ['node' => '@node.node_route_context:node'],
    ]);

    $search_text = 'Nothing in particular';
    $this->drupalGet($dir_channel_page_path, ['query' => ['search_api_fulltext' => $search_text]]);

    // Reload directory channel, but don't search anything.
    $this->drupalGet($dir_channel_page_path);
    // The above mentioned search text should *not* be present on the page.
    $this->assertSession()->elementExists('css', '[data-drupal-selector=edit-search-api-fulltext][value=""]');
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_directories_db',
  ];

}
