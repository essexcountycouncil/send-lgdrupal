<?php

namespace Drupal\Tests\localgov_guides\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests user blocks.
 *
 * @group localgov_guides
 */
class ContentsBlockTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'path',
    'options',
    'localgov_guides',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with the 'administer blocks' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('localgov_guides_contents');
    $this->drupalLogout();
  }

  /**
   * Tests that block is only visible on guide pages.
   */
  public function testContentsBlockVisibility() {
    $overview = $this->createNode([
      'title' => 'Guide overview',
      'type' => 'localgov_guides_overview',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $page = $this->createNode([
      'title' => 'Guide page',
      'type' => 'localgov_guides_page',
      'status' => NodeInterface::PUBLISHED,
      'localgov_guides_parent' => ['target_id' => $overview->id()],
    ]);

    $orphan = $this->createNode([
      'title' => 'Guide page',
      'type' => 'localgov_guides_page',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalGet('node');
    $this->assertSession()->responseNotContains('block-localgov-guides-contents');

    $this->drupalGet($overview->toUrl()->toString());
    $this->assertSession()->responseContains('block-localgov-guides-contents');

    $this->drupalGet($page->toUrl()->toString());
    $this->assertSession()->responseContains('block-localgov-guides-contents');

    $this->drupalGet($orphan->toUrl()->toString());
    $this->assertSession()->responseNotContains('block-localgov-guides-contents');
  }

  /**
   * Test the contents list block.
   */
  public function testContentListBlock() {
    $overview = $this->createNode([
      'title' => 'Guide overview',
      'type' => 'localgov_guides_overview',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $pages = [];
    for ($i = 0; $i < 3; $i++) {
      $pages[] = $this->createNode([
        'title' => 'Guide page ' . $i,
        'type' => 'localgov_guides_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_guides_parent' => ['target_id' => $overview->id()],
      ]);
    }

    // Check overview.
    $this->drupalGet($overview->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(4, $results);
    $this->assertStringContainsString('Guide overview', $results[0]->getText());
    $this->assertStringNotContainsString($overview->toUrl()->toString(), $results[0]->getHtml());
    $this->assertStringContainsString('Guide page 0', $results[1]->getText());
    $this->assertStringContainsString($pages[0]->toUrl()->toString(), $results[1]->getHtml());
    $this->assertStringContainsString('Guide page 1', $results[2]->getText());
    $this->assertStringContainsString($pages[1]->toUrl()->toString(), $results[2]->getHtml());
    $this->assertStringContainsString('Guide page 2', $results[3]->getText());
    $this->assertStringContainsString($pages[2]->toUrl()->toString(), $results[3]->getHtml());

    // Check page.
    $this->drupalGet($pages[0]->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(4, $results);
    $this->assertStringContainsString('Guide overview', $results[0]->getText());
    $this->assertStringContainsString($overview->toUrl()->toString(), $results[0]->getHtml());
    $this->assertStringContainsString('Guide page 0', $results[1]->getText());
    $this->assertStringNotContainsString($pages[0]->toUrl()->toString(), $results[1]->getHtml());
    $this->assertStringContainsString('Guide page 1', $results[2]->getText());
    $this->assertStringContainsString($pages[1]->toUrl()->toString(), $results[2]->getHtml());
    $this->assertStringContainsString('Guide page 2', $results[3]->getText());
    $this->assertStringContainsString($pages[2]->toUrl()->toString(), $results[3]->getHtml());

    // Check caching.
    $pages[] = $this->createNode([
      'title' => 'Guide page 3',
      'type' => 'localgov_guides_page',
      'status' => NodeInterface::PUBLISHED,
      'localgov_guides_parent' => ['target_id' => $overview->id()],
    ]);
    $this->drupalGet($overview->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(5, $results);
    $this->assertSession()->responseContains('Guide page 3');
    // Change title.
    $pages[2]->title = 'New title page 2';
    $pages[2]->save();
    $this->drupalGet($overview->toUrl()->toString());
    $this->assertSession()->responseNotContains('Guide page 2');
    $this->assertSession()->responseContains('New title page 2');

    // Another overview.
    $overview_2 = $this->createNode([
      'title' => 'Guide overview 2',
      'type' => 'localgov_guides_overview',
      'status' => NodeInterface::PUBLISHED,
    ]);
    // Move a current revision to it.
    $pages[0]->localgov_guides_parent = ['target_id' => $overview_2->id()];
    $pages[0]->setNewRevision();
    $pages[0]->save();
    // Check overview.
    $this->drupalGet($overview->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(4, $results);
    $this->assertStringNotContainsString('Guide page 0', $results[1]->getText());
    // Check new overview.
    $this->drupalGet($overview_2->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(2, $results);
    $this->assertStringContainsString('Guide overview', $results[0]->getText());
    $this->assertStringNotContainsString($overview_2->toUrl()->toString(), $results[0]->getHtml());
    $this->assertStringContainsString('Guide page 0', $results[1]->getText());
    $this->assertStringContainsString($pages[0]->toUrl()->toString(), $results[1]->getHtml());

    // Unpublish a page.
    $pages[1]->status = NodeInterface::NOT_PUBLISHED;
    $pages[1]->save();
    // Still linked.
    $content_admin = $this->drupalCreateUser(['bypass node access']);
    $this->drupalLogin($content_admin);
    $this->drupalGet($overview->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(4, $results);
    $this->assertSession()->responseContains('Guide page 1');
    $this->drupalLogout();
    // But not for anon.
    $this->drupalGet($overview->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(3, $results);
    $this->assertSession()->responseNotContains('Guide page 1');

    // Delete page.
    $pages[3]->delete();
    $this->drupalGet($overview->toUrl()->toString());
    $xpath = '//ul[@class="progress"]/li';
    $results = $this->xpath($xpath);
    $this->assertCount(2, $results);
    $this->assertSession()->responseNotContains('Guide page 3');
  }

}
