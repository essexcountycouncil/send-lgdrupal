<?php

namespace Drupal\Tests\localgov_search\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Base test for server modules to check sitewide search integration.
 *
 * @group localgov_search
 */
class SitewideSearchBase extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Use testing profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Use stark theme.
   *
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_search',
    'block',
    'big_pipe',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }
  }

  /**
   * Test sidewide search works.
   */
  public function testSitewideSearch() {

    // Create a content type.
    $bundle = 'test';
    $this->drupalCreateContentType([
      'type' => $bundle,
      'name' => 'Test node type',
    ]);

    // Create search index display mode.
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => $bundle,
      'mode' => 'search_index',
      'status' => TRUE,
      'content' => [
        'body' => [
          'label' => 'hidden',
          'type' => 'text_default',
          'settings' => [],
          'third_party_settings' => [],
          'region' => 'content',
          'weight' => 0,
        ],
      ],
    ])->save();

    // Create search result display mode.
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => $bundle,
      'mode' => 'search_result',
      'status' => TRUE,
      'content' => [
        'body' => [
          'label' => 'hidden',
          'type' => 'text_summary_or_trimmed',
          'settings' => [
            'trim_length' => 600,
          ],
          'third_party_settings' => [],
          'region' => 'content',
          'weight' => 0,
        ],
      ],
    ])->save();

    // Create a couple of nodes with content.
    $title1 = $this->randomMachineName(8);
    $body1 = $this->randomMachineName(32);
    $summary1 = $this->randomMachineName(16);
    $this->drupalCreateNode([
      'type' => $bundle,
      'title' => $title1,
      'body' => [
        'value' => $body1,
        'summary' => $summary1,
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $title2 = $this->randomMachineName(8);
    $body2 = $this->randomMachineName(32);
    $summary2 = $this->randomMachineName(16);
    $this->drupalCreateNode([
      'type' => $bundle,
      'title' => $title2,
      'body' => [
        'value' => $body2,
        'summary' => $summary2,
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->indexItems();

    // Check search form.
    $url = Url::fromRoute('view.localgov_sitewide_search.sitewide_search_page');
    $this->drupalGet($url);
    $this->assertSession()->elementExists('css', '#views-exposed-form-localgov-sitewide-search-sitewide-search-page');
    $this->assertSession()->elementAttributeContains('css', '#views-exposed-form-localgov-sitewide-search-sitewide-search-page', 'role', 'search');
    $this->assertSession()->pageTextNotContains('No results');

    // Check searches.
    $this->submitForm(['s' => $title1], 'Apply');
    $this->assertSession()->pageTextContains($title1);
    $this->assertSession()->pageTextContains($summary1);
    $this->assertSession()->pageTextNotContains($body1);
    $this->submitForm(['s' => $body2], 'Apply');
    $this->assertSession()->pageTextContains($title2);
    $this->assertSession()->pageTextContains($summary2);

    // Check caching of search field.
    $url_front = Url::fromRoute('<front>');
    $url_search = Url::fromRoute('view.localgov_sitewide_search.sitewide_search_page');
    $url_search_title = Url::fromRoute('view.localgov_sitewide_search.sitewide_search_page', [], ['query' => ['s' => $title1]]);
    $url_search_canary = Url::fromRoute('view.localgov_sitewide_search.sitewide_search_page', [], ['query' => ['s' => 'canary']]);
    // Search page.
    $this->drupalGet($url_search_title);
    $this->assertSession()->pageTextContains($title1);
    $this->drupalGet($url_search_title);
    $this->drupalGet($url_search);
    $this->assertSession()->pageTextNotContains($title1);
    // Block.
    $this->drupalPlaceBlock('localgov_sitewide_search_block', ['region' => 'header']);
    $this->drupalGet($url_search_canary);
    $this->drupalGet($url_front);
    $this->assertSession()->responseNotContains('canary');
  }

  /**
   * Run index items.
   *
   * Allow backend specific tests to override.
   */
  protected function indexItems() {
    // Index content.
    $index = Index::load('localgov_sitewide_search');
    $index->indexItems();
  }

}
