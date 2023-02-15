<?php

declare(strict_types = 1);

namespace Drupal\tests\localgov_services_sublanding\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\NodeInterface;
use Drupal\Component\Utility\Random;
use Drupal\Core\Url;

/**
 * Functional tests for the LinkNodeReference field formatter.
 *
 * Tests with:
 *  - external link,
 *  - node link,
 *  - internal non-node link.
 *
 * See also UnpublishedLinkNodeReferenceTest.
 *
 * @group localgov_services_sublanding
 */
class LinkFormatterTest extends BrowserTestBase {

  /**
   * Theme to use during the functional tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['localgov_services_sublanding'];

  /**
   * Service sub page node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $serviceSubPage;

  /**
   * Content editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * Setup Service sub page and an editor user capable of editing it.
   */
  public function setUp(): void {

    parent::setUp();

    $this->editorUser = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($this->editorUser);

    $this->serviceSubPage = $this->drupalCreateNode([
      'type' => 'localgov_services_sublanding',
      'title' => $this->randomString(),
      'status' => NodeInterface::PUBLISHED,
      'body' => [
        [
          'summary' => 'No summary',
          'value' => (new Random)->paragraphs(3),
          'format' => \filter_default_format(),
        ],
      ],
    ]);
  }

  /**
   * Test link formatter with a Node.
   */
  public function testNode() {
    // Create a Service page which will be used as the child page.
    $child_page = $this->drupalCreateNode([
      'title'  => $this->randomString(),
      'status' => NodeInterface::PUBLISHED,
      'type'   => 'localgov_services_page',
      'localgov_services_parent' => [
        [
          'target_id' => $this->serviceSubPage->id(),
        ],
      ],
    ]);
    $this->childPageLastNid = $child_page->id();

    // Add child page to Service sub page.
    $this->drupalGet($this->serviceSubPage->toUrl('edit-form')->toString());
    $this->submitForm([], 'Add Topic list builder');

    $this->getSession()->getPage()->fillField('localgov_topics[0][subform][topic_list_links][0][uri]', $child_page->title->value . ' (' . $child_page->id() . ')');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('Error message');

    $this->drupalGet($this->serviceSubPage->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->serviceSubPage->getTitle());
    $this->assertEquals($child_page->toUrl()->toString(), $this->getSession()->getPage()->findLink($child_page->getTitle())->getAttribute('href'));
    $this->assertSession()->ResponseNotContains('localgov-services-sublanding-child-entity--unpublished');
  }

  /**
   * Test link formatter with an external link.
   */
  public function testExternal() {
    $this->drupalGet($this->serviceSubPage->toUrl('edit-form')->toString());
    $this->submitForm([], 'Add Topic list builder');

    $this->getSession()->getPage()->fillField('localgov_topics[0][subform][topic_list_links][0][uri]', 'https://example.com');
    $this->getSession()->getPage()->fillField('localgov_topics[0][subform][topic_list_links][0][title]', 'Example external link');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('Error message');

    $this->drupalGet($this->serviceSubPage->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->serviceSubPage->getTitle());
    $this->assertEquals('https://example.com', $this->getSession()->getPage()->findLink('Example external link')->getAttribute('href'));
    $this->assertSession()->ResponseNotContains('localgov-services-sublanding-child-entity--unpublished');
  }

  /**
   * Test link formatter with an internal, non-node, link.
   */
  public function testInternalNotEntity() {
    $this->drupalGet($this->serviceSubPage->toUrl('edit-form')->toString());
    $this->submitForm([], 'Add Topic list builder');

    $this->getSession()->getPage()->fillField('localgov_topics[0][subform][topic_list_links][0][uri]', '<front>');
    $this->getSession()->getPage()->fillField('localgov_topics[0][subform][topic_list_links][0][title]', 'Front page link');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('Error message');

    $this->drupalGet($this->serviceSubPage->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->serviceSubPage->getTitle());
    $this->assertEquals(Url::fromUri('internal:/')->toString(), $this->getSession()->getPage()->findLink('Front page link')->getAttribute('href'));
    $this->assertSession()->ResponseNotContains('localgov-services-sublanding-child-entity--unpublished');
  }

}
