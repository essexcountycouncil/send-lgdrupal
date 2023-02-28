<?php

namespace Drupal\Tests\localgov_news\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests LocalGov News article page.
 *
 * @group localgov_news
 */
class NewsPageTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * Test breadcrumbs in the Standard profile.
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
    'localgov_core',
    'localgov_media',
    'localgov_topics',
    'localgov_news',
    'field_ui',
    'pathauto',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'administer node fields',
    ]);
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    // Newsroom.
    $this->createNode([
      'title' => 'News',
      'type' => 'localgov_newsroom',
      'status' => NodeInterface::PUBLISHED,
    ]);
  }

  /**
   * Verifies basic functionality with all modules.
   */
  public function testNewsFields() {
    $this->drupalLogin($this->adminUser);

    // Check news article fields.
    $this->drupalGet('/admin/structure/types/manage/localgov_news_article/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('body');
    $this->assertSession()->pageTextContains('localgov_news_categories');
    $this->assertSession()->pageTextContains('localgov_news_date');
    $this->assertSession()->pageTextContains('field_media_image');
    $this->assertSession()->pageTextContains('localgov_news_related');
    $this->assertSession()->pageTextContains('localgov_newsroom');
  }

  /**
   * Test node edit forms.
   */
  public function testNewsEditForm() {
    // Filling in the media field is a bit fiddly.
    $media_field = $this->container
      ->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('node.localgov_news_article.field_media_image');
    $media_field->setRequired(FALSE);
    $media_field->save();

    $this->drupalLogin($this->adminUser);
    // By default there should not be a newsroom field displayed,
    // but all news goes into the one newsroom.
    $this->drupalGet('/node/add/localgov_news_article');
    $assert = $this->assertSession();
    $assert->fieldNotExists('edit-localgov-newsroom');
    $this->submitForm([
      'Title' => 'News article',
      'Summary' => 'Article summary',
      'Body' => 'Article body',
    ], 'Save');
    $newsroom = $this->getNodeByTitle('News');
    $article = $this->getNodeByTitle('News article');
    $this->assertEquals($article->localgov_newsroom->target_id, $newsroom->id());

    // Second newsroom.
    $newsroom_2 = $this->createNode([
      'title' => 'Second newsroom',
      'type' => 'localgov_newsroom',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/add/localgov_news_article');
    $assert->fieldExists('edit-localgov-newsroom');

    $this->drupalGet($article->toUrl('edit-form'));
    $this->submitForm([
      'Title' => 'News article',
      'Summary' => 'Article summary',
      'Body' => 'Article body',
      'Newsroom' => $newsroom_2->id(),
    ], 'Save');
    $this->nodeStorage->resetCache();
    $article = $this->nodeStorage->load($article->id());
    $this->assertEquals($article->localgov_newsroom->target_id, $newsroom_2->id());
  }

  /**
   * Test node edit form promote checkbox.
   */
  public function testNewsEditPromoteCheckbox() {
    // Filling in the media field is a bit fiddly.
    $media_field = $this->container
      ->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('node.localgov_news_article.field_media_image');
    $media_field->setRequired(FALSE);
    $media_field->save();

    $this->drupalLogin($this->adminUser);
    // Add article.
    $this->drupalGet('/node/add/localgov_news_article');
    $this->submitForm([
      'Title' => 'News article 1',
      'Summary' => 'Article summary',
      'Body' => 'Article body',
      'Promote on newsroom' => 1,
      'Published' => 1,
    ], 'Save');
    $newsroom = $this->getNodeByTitle('News');
    $article = $this->getNodeByTitle('News article 1');
    $promoted = $newsroom->localgov_newsroom_featured->getValue();
    $this->assertTrue(in_array(['target_id' => $article->id()], $promoted));

    // Remove article.
    $this->drupalGet($article->toUrl('edit-form'));
    $this->submitForm([
      'Promote on newsroom' => 0,
    ], 'Save');
    $this->nodeStorage->resetCache();
    $newsroom = $this->nodeStorage->load($newsroom->id());
    $promoted = $newsroom->localgov_newsroom_featured->getValue();
    $this->assertFalse(in_array(['target_id' => $article->id()], $promoted));

    // Fill featured items.
    for ($i = 2; $i < 5; $i++) {
      $this->drupalGet('/node/add/localgov_news_article');
      $this->submitForm([
        'Title' => 'News article ' . $i,
        'Summary' => 'Article summary',
        'Body' => 'Article body',
        'Promote on newsroom' => 1,
        'Published' => 1,
      ], 'Save');
    }
    $this->nodeStorage->resetCache();
    $newsroom = $this->nodeStorage->load($newsroom->id());
    $promoted = $newsroom->localgov_newsroom_featured->getValue();
    $this->assertFalse(in_array(['target_id' => $article->id()], $promoted));
    $article = $this->getNodeByTitle('News article 2');
    $this->assertTrue(in_array(['target_id' => $article->id()], $promoted));
    $article = $this->getNodeByTitle('News article 3');
    $this->assertTrue(in_array(['target_id' => $article->id()], $promoted));
    $article = $this->getNodeByTitle('News article 4');
    $this->assertTrue(in_array(['target_id' => $article->id()], $promoted));

    // Add one more first pushed off.
    $this->drupalGet('/node/add/localgov_news_article');
    $this->submitForm([
      'Title' => 'News article 5',
      'Summary' => 'Article summary',
      'Body' => 'Article body',
      'Promote on newsroom' => 1,
      'Published' => 1,
    ], 'Save');
    $this->nodeStorage->resetCache();
    $newsroom = $this->nodeStorage->load($newsroom->id());
    $promoted = $newsroom->localgov_newsroom_featured->getValue();
    $article = $this->getNodeByTitle('News article 2');
    $this->assertFalse(in_array(['target_id' => $article->id()], $promoted));
    $article = $this->getNodeByTitle('News article 3');
    $this->assertTrue(in_array(['target_id' => $article->id()], $promoted));
    $article = $this->getNodeByTitle('News article 4');
    $this->assertTrue(in_array(['target_id' => $article->id()], $promoted));
    $article = $this->getNodeByTitle('News article 5');
    $this->assertTrue(in_array(['target_id' => $article->id()], $promoted));
  }

  /**
   * News article, newsroom, featured news.
   */
  public function testNewsPages() {
    $news_articles = [];
    // Default newsroom generated on install.
    $newsroom = $this->getNodeByTitle('News');

    // Post news into default newsroom.
    $body = $this->randomMachineName(64);
    $news_articles[1] = $this->createNode([
      'title' => 'News article 1',
      'body' => $body,
      'type' => 'localgov_news_article',
      'status' => NodeInterface::PUBLISHED,
      'localgov_newsroom' => ['target_id' => $newsroom->id()],
    ]);
    // News is the default path of the default $newsroom.
    $this->drupalGet('news/' . date('Y') . '/news-article-1');
    $this->assertSession()->pageTextContains('News article 1');
    $this->assertSession()->pageTextContains($body);
    $this->drupalGet('news');
    $this->assertSession()->elementContains('css', 'div.view--teasers', 'News article 1');

    // Second newsroom. Alias will be second-newsroom.
    $newsroom_2 = $this->createNode([
      'title' => 'Second newsroom',
      'type' => 'localgov_newsroom',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('second-newsroom');
    $this->assertSession()->pageTextContains('Second newsroom');
    $this->assertSession()->pageTextNotContains('News article 1');

    // Post news into the second newsroom.
    $body = $this->randomMachineName(64);
    $news_articles[2] = $this->createNode([
      'title' => 'News article 2',
      'body' => $body,
      'type' => 'localgov_news_article',
      'status' => NodeInterface::PUBLISHED,
      'localgov_newsroom' => ['target_id' => $newsroom_2->id()],
    ]);
    $this->drupalGet('second-newsroom/' . date('Y') . '/news-article-2');
    $this->assertSession()->pageTextContains('News article 2');
    $this->drupalGet('second-newsroom');
    $this->assertSession()->pageTextContains('Second newsroom');
    $this->assertSession()->pageTextNotContains('News article 1');
    $this->assertSession()->pageTextContains('News article 2');

    // Add News article 1 to the featured news block.
    $newsroom->set('localgov_newsroom_featured', ['target_id' => $news_articles[1]->id()]);
    $newsroom->save();
    $this->drupalGet($newsroom->toUrl());
    $this->assertSession()->elementContains('css', 'div.newsroom__featured-news', 'News article 1');

    for ($i = 3; $i < 10; $i++) {
      $news_articles[$i] = $this->createNode([
        'title' => 'News article ' . $i,
        'body' => $this->randomString(250),
        'type' => 'localgov_news_article',
        'status' => NodeInterface::PUBLISHED,
        'localgov_newsroom' => ['target_id' => $newsroom->id()],
      ]);
    }
    $this->drupalGet($newsroom->toUrl());
    for ($i = 3; $i < 10; $i++) {
      $this->assertSession()->elementContains('css', 'div.view--teasers', 'News article ' . $i);
    }
    $newsroom->set('localgov_newsroom_featured', [
      ['target_id' => $news_articles[3]->id()],
      ['target_id' => $news_articles[5]->id()],
      ['target_id' => $news_articles[4]->id()],
    ]);
    $newsroom->save();
    $this->drupalGet($newsroom->toUrl());
    $this->assertSession()->elementNotContains('css', 'div.newsroom__featured-news', 'News article 1');
    $this->assertSession()->elementContains('css', 'div.newsroom__featured-news', 'News article 3');
    $this->assertSession()->elementContains('css', 'div.newsroom__featured-news', 'News article 5');
    $this->assertSession()->elementContains('css', 'div.newsroom__featured-news', 'News article 4');
  }

}
