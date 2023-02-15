<?php

namespace Drupal\Tests\localgov_news\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests LocalGov News search.
 *
 * @group localgov_news
 */
class NewsSearchTest extends BrowserTestBase {

  use NodeCreationTrait;
  use CronRunTrait;

  /**
   * Test search in the Localgov profile.
   *
   * @var string
   */
  protected $profile = 'localgov';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'localgov_news',
    'localgov_search',
    'localgov_search_db',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);
    $this->drupalLogin($this->adminUser);

    $body = [
      'value' => 'Science is the search for truth, that is the effort to understand the world: it involves the rejection of bias, of dogma, of revelation, but not the rejection of morality.',
      'summary' => 'One of the greatest joys known to man is to take a flight into ignorance in search of knowledge.',
    ];

    // Newsroom.
    $newsroom = $this->createNode([
      'title' => 'News',
      'type' => 'localgov_newsroom',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->createNode([
      'title' => 'Test News Article',
      'body' => $body,
      'type' => 'localgov_news_article',
      'status' => NodeInterface::PUBLISHED,
      'localgov_newsroom' => ['target_id' => $newsroom->id()],
      'localgov_news_date' => ['value' => '2020-06-01'],
    ]);

    $this->drupalLogout();
    $this->cronRun();
  }

  /**
   * Basic search functionality.
   */
  public function testNewsSearch() {

    // Defaults to be on 'news' page.
    $this->drupalGet('news');
    $this->submitForm(['edit-search-api-fulltext' => 'dogma'], 'Apply', 'views-exposed-form-localgov-news-search-page-search-news');
    $this->assertSession()->pageTextContains('Test News Article');

    // Defaults to be on 'news' path page.
    $this->drupalGet("news/2020/test-news-article");
    $this->submitForm(['edit-search-api-fulltext' => 'dogma'], 'Apply', 'views-exposed-form-localgov-news-search-page-search-news');
    $this->assertSession()->pageTextContains('Test News Article');

    $this->drupalGet("news/2020/test-news-article");
    $this->submitForm(['edit-search-api-fulltext' => 'xyzzy'], 'Apply', 'views-exposed-form-localgov-news-search-page-search-news');
    $this->assertSession()->pageTextNotContains('Test News Article');
  }

  /**
   * LocalGov Search integration.
   */
  public function testLocalgovSearch() {
    $this->drupalGet('search', ['query' => ['s' => 'bias+dogma+revelation']]);
    $this->assertSession()->pageTextContains('Test News Article');
    $this->assertSession()->responseContains('<strong>bias</strong>');
    $this->assertSession()->responseContains('<strong>dogma</strong>');
    $this->assertSession()->responseContains('<strong>revelation</strong>');
  }

}
