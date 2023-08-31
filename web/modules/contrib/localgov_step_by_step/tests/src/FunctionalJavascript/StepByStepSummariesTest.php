<?php

namespace Drupal\Tests\localgov_step_by_step\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;

/**
 * Javascript tests for LocalGovDrupal step-by-step blocks.
 */
class StepByStepSummariesTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_step_by_step',
  ];

  /**
   * Test step summary visibility.
   */
  public function testStepSummaryVisibility() {

    // Create step-by-step overview node.
    $overview_title = $this->randomMachineName(8);
    $overview = $this->createNode([
      'title' => $overview_title,
      'type' => 'localgov_step_by_step_overview',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $step_pages = [];
    // Create three step-by-step page nodes.
    for ($x = 1; $x < 4; $x++) {
      $step_pages[$x] = $this->createNode([
        'title' => 'Step ' . $x . ' page',
        'localgov_step_section_title' => 'Step ' . $x . ' title',
        'type' => 'localgov_step_by_step_page',
        'localgov_step_summary' => 'Step ' . $x . ' summary.',
        'status' => NodeInterface::PUBLISHED,
        'localgov_step_parent' => ['target_id' => $overview->id()],
      ]);
    }

    $this->drupalPlaceBlock('step_part_of_block', ['region' => 'sidebar_second']);

    // Load overview page.
    $this->drupalGet('/node/1');

    // Check summaries not visible.
    $this->assertSession()->pageTextNotContains('Step 1 summary');

    $page = $this->getSession()->getPage();

    // Test 'Show summaries' button.
    $page->pressButton('Show summaries');
    $this->assertSession()->pageTextContains('Step 1 summary');
    $this->assertSession()->pageTextContains('Step 2 summary');
    $this->assertSession()->pageTextContains('Step 3 summary');

    // Test 'Hide summaries' button.
    $page->pressButton('Hide summaries');
    $this->assertSession()->pageTextNotContains('Step 1 summary');
    $this->assertSession()->pageTextNotContains('Step 2 summary');
    $this->assertSession()->pageTextNotContains('Step 3 summary');

    // Load step 2 page and test summary visibility.
    $this->drupalGet('/node/3');
    $this->assertSession()->pageTextNotContains('Step 1 summary');
    $this->assertSession()->pageTextNotContains('Step 3 summary');
    $this->assertSession()->pageTextContains('Step 2 summary');

    // Unpublish step 2.
    $step_pages[2]->status = NodeInterface::NOT_PUBLISHED;
    $step_pages[2]->save();
    $this->drupalGet('/node/1');
    // Test 'Show summaries' button.
    $page->pressButton('Show summaries');
    $this->assertSession()->pageTextContains('Step 1 summary');
    $this->assertSession()->pageTextNotContains('Step 2 summary');
    $this->assertSession()->pageTextContains('Step 3 summary');

    // Delete step 3.
    $step_pages[3]->delete();
    $this->drupalGet('/node/1');
    // Test 'Show summaries' button.
    $page->pressButton('Show summaries');
    $this->assertSession()->pageTextContains('Step 1 summary');
    $this->assertSession()->pageTextNotContains('Step 2 summary');
    $this->assertSession()->pageTextNotContains('Step 3 summary');
  }

}
