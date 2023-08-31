<?php

namespace Drupal\Tests\localgov_news\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests newsroom restricted entity reference autocomplete.
 *
 * @group localgov_news
 */
class PromotedNewsWidgetTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;

  /**
   * Newsrooms.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $newsrooms;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'localgov_core',
    'localgov_media',
    'localgov_topics',
    'localgov_news',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a Content type and two test nodes.
    $this->createNode(['title' => 'Test page']);
    $this->createNode(['title' => 'Page test']);

    $this->newsrooms[0] = $this->createNode([
      'title' => 'Newsroom 0',
      'type' => 'localgov_newsroom',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->newsrooms[1] = $this->createNode([
      'title' => 'Newsroom 1',
      'type' => 'localgov_newsroom',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $user = $this->drupalCreateUser([
      'access content',
      'create localgov_newsroom content',
      'edit any localgov_newsroom content',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests newsroom featured autocomplete widget results.
   */
  public function testPromoteAutocompleteWidget() {
    for ($i = 1; $i < 4; $i++) {
      $this->createNode([
        'title' => 'News article ' . $i,
        'body' => $this->randomString(250),
        'type' => 'localgov_news_article',
        'status' => NodeInterface::PUBLISHED,
        'localgov_newsroom' => ['target_id' => $this->newsrooms[0]->id()],
      ]);
    }
    for ($i = 4; $i < 7; $i++) {
      $this->createNode([
        'title' => 'News article ' . $i,
        'body' => $this->randomString(250),
        'type' => 'localgov_news_article',
        'status' => NodeInterface::PUBLISHED,
        'localgov_newsroom' => ['target_id' => $this->newsrooms[1]->id()],
      ]);
    }

    // Visit the node add page.
    $this->drupalGet('node/add/localgov_newsroom');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="localgov_newsroom_featured[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Test');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(0, $results);

    // Visit the one of the existing newsrooms with articles.
    $this->drupalGet($this->newsrooms[1]->toUrl('edit-form'));
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="localgov_newsroom_featured[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('art');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
    $assert_session->pageTextContains('News article 4');
    $assert_session->pageTextContains('News article 5');
    $assert_session->pageTextContains('News article 6');
  }

}
