<?php

declare(strict_types = 1);

namespace Drupal\tests\localgov_services_sublanding\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Random;

/**
 * Functional tests for the LinkNodeReference field formatter.
 *
 * Tests that:
 * - Unpublished child nodes are not listed by the parent page to anonymous
 *   users.
 * - When an unpublished node is added as a child page and later published, it
 *   should be listed by the parent page for anonymous users.
 * - Unpublished child nodes are clearly highlighted to editors.
 *
 * @group localgov_services_sublanding
 */
class UnpublishedLinkNodeReferenceTest extends BrowserTestBase {

  /**
   * Setup Service sub page and an editor user capable of editing it.
   *
   * Create an unpublished Service page and set it as a child page of a new
   * Service sub page.  The LinkNodeReference field formatter of the parent page
   * lists this child page as a teaser.
   */
  public function setUp(): void {

    parent::setUp();

    $this->editorUser = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($this->editorUser);

    $service_sub_page = $this->drupalCreateNode([
      'type' => 'localgov_services_sublanding',
      'title' => self::SERVICE_SUB_PAGE_TITLE,
      'status' => NodeInterface::PUBLISHED,
      'body' => [
        [
          'summary' => 'No summary',
          'value' => (new Random)->paragraphs(3),
          'format' => filter_default_format(),
        ],
      ],
    ]);
    $this->serviceSubPageLastNid = $service_sub_page->id();

    // Create a Service page which will be used as the child page.
    $child_page = $this->drupalCreateNode([
      'title'  => self::CHILD_PAGE_TITLE,
      'status' => NodeInterface::NOT_PUBLISHED,
      'type'   => 'localgov_services_page',
      'localgov_services_parent' => [
        [
          'target_id' => $this->serviceSubPageLastNid,
        ],
      ],
    ]);
    $this->childPageLastNid = $child_page->id();

    // Add child page to Service sub page.
    $this->drupalGet("node/{$this->serviceSubPageLastNid}/edit");
    $this->submitForm([], 'Add Topic list builder');

    $this->getSession()->getPage()->fillField('localgov_topics[0][subform][topic_list_links][0][uri]', self::CHILD_PAGE_TITLE . " ($this->childPageLastNid)");
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains(self::FORM_ERROR_MSG);

    $this->drupalLogout();

    // Create a cached copy of the Service sub page.
    $this->drupalGet("node/{$this->serviceSubPageLastNid}");
  }

  /**
   * Service sub pages should only list **published** child pages.
   *
   * This has to be true even when the child page was added when it was still
   * unpublished.
   */
  public function testOnlyListPublishedChildPages() {

    $this->doNotListUnpublishedChildPages();
    $this->publishChildPage();
    $this->listPublishedChildPages();
  }

  /**
   * Unpublished child pages should be clearly highlighted to editors.
   *
   * The presence of the localgov-services-sublanding-child-entity--unpublished
   * DOM class is indicative of this highlighting.
   */
  public function testHighlightUnpublishedChildPages() {

    $this->drupalLogin($this->editorUser);

    $this->drupalGet("node/{$this->serviceSubPageLastNid}");
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains(self::CHILD_PAGE_TITLE);
    $this->assertSession()->ResponseContains('localgov-services-sublanding-child-entity--unpublished');
  }

  /**
   * Service sub pages should not list **unpublished** child pages.
   */
  protected function doNotListUnpublishedChildPages() {

    $this->drupalGet("node/{$this->serviceSubPageLastNid}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(self::SERVICE_SUB_PAGE_TITLE);
    $this->assertSession()->pageTextNotContains(self::CHILD_PAGE_TITLE);

    // No *empty* item for any unpublished child page link should be present.
    $this->assertSession()->elementNotExists('css', '.topic-list-item');
  }

  /**
   * An editor publishes the unpublished child page.
   */
  protected function publishChildPage() {

    $this->drupalLogin($this->editorUser);
    $child_node = Node::load($this->childPageLastNid);
    $child_node->status = NodeInterface::PUBLISHED;
    $child_node->save();
    $this->drupalLogout();
  }

  /**
   * Service sub pages should only list **published** child pages.
   *
   * View the Service sub page as an anonymous user.  It should list the
   * child page.
   */
  protected function listPublishedChildPages() {

    $this->drupalGet("node/{$this->serviceSubPageLastNid}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(self::SERVICE_SUB_PAGE_TITLE);
    $this->assertSession()->pageTextContains(self::CHILD_PAGE_TITLE);
    $this->assertSession()->ResponseNotContains('localgov-services-sublanding-child-entity--unpublished');
  }

  /**
   * Node id of the last generated Service sub page.
   *
   * @var int
   */
  protected $serviceSubPageLastNid = -1;

  /**
   * Node id of the last generated child page.
   *
   * @var int
   */
  protected $childPageLastNid = -1;

  /**
   * Content editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * As they say on their tins.
   */
  const SERVICE_SUB_PAGE_TITLE = 'Service sub page title';
  const CHILD_PAGE_TITLE = 'Child page title';
  const FORM_ERROR_MSG = 'Error message';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['localgov_services_sublanding'];

  /**
   * Theme to use during the functional tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

}
