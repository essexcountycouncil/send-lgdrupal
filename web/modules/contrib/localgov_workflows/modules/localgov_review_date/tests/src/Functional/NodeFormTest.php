<?php

namespace Drupal\Tests\localgov_review_date\Functional;

use Drupal\localgov_review_date\Entity\ReviewDate;
use Drupal\scheduled_transitions\Entity\ScheduledTransition;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the Review Status node form UI.
 *
 * @group path
 */
class NodeFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_review_date',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a page content type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Add page to localgov_editorial workflow.
    $editorial = Workflow::load('localgov_editorial');
    $type = $editorial->getTypePlugin();
    $type->addEntityTypeAndBundle('node', 'page');
    $editorial->save();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests the node form ui.
   */
  public function testNodeForm() {
    $assert_session = $this->assertSession();

    // Check review status widget doesn't display if schedule transitions are
    // not configured.
    $this->drupalGet('node/add/page');
    $assert_session->elementNotExists('css', '.review-date-form');
    $assert_session->fieldNotExists('localgov_review_date[0][reviewed]');

    // Configure scheduled transitions to work with page.
    $this->drupalGet('admin/config/workflow/scheduled-transitions');
    $edit = [
      'enabled[node:page]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check review status now displays when adding a page.
    $this->drupalGet('node/add/page');
    $assert_session->elementContains('css', '.review-date-form summary', 'Review date');
    $assert_session->fieldExists('localgov_review_date[0][reviewed]');
    $assert_session->fieldExists('localgov_review_date[0][review][review_in]');
    $assert_session->fieldExists('localgov_review_date[0][review][review_date]');

    // Check review status widget can be disabled on a content type.
    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'page', 'default')
      ->removeComponent('localgov_review_date')
      ->save();
    $this->drupalGet('node/add/page');
    $assert_session->elementNotExists('css', '.review-date-form');
    $assert_session->fieldNotExists('localgov_review_date[0][reviewed]');
  }

  /**
   * Tests the scheduled transition creation and updating·.
   */
  public function testScheduledTransitions() {

    // Configure scheduled transitions to work with page.
    $this->drupalGet('admin/config/workflow/scheduled-transitions');
    $edit = [
      'enabled[node:page]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check scheduled transition created when reviewing node.
    $this->drupalGet('node/add/page');
    $title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $title,
      'localgov_review_date[0][reviewed]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($title);
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $review_date = ReviewDate::getActiveReviewDate($node, $langcode);
    $this->assertInstanceOf('Drupal\localgov_review_date\Entity\ReviewDate', $review_date);
    $this->assertEquals(1, $review_date->id());
    $scheduled_transition = $review_date->getScheduledTransition();
    $this->assertInstanceOf('Drupal\scheduled_transitions\Entity\ScheduledTransition', $scheduled_transition);
    $this->assertEquals(1, $scheduled_transition->id());
    $this->assertEquals($review_date->getReviewTime(), $scheduled_transition->getTransitionTime());

    // Check scheduled transition gets updated on node save.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $next_review = date('Y-m-d', strtotime('+3 months'));
    $edit = [
      'localgov_review_date[0][reviewed]' => TRUE,
      'localgov_review_date[0][review][review_date]' => $next_review,
    ];
    $this->submitForm($edit, 'Save');
    $this->container->get('entity_type.manager')->getStorage('scheduled_transition')->resetCache([1]);
    $review_date = ReviewDate::getActiveReviewDate($node, $langcode);
    $this->assertEquals(2, $review_date->id());
    $this->assertEquals(strtotime($next_review), $review_date->getReviewTime());
    $scheduled_transition = $review_date->getScheduledTransition();
    $this->assertEquals(1, $scheduled_transition->id());
    $this->assertEquals($review_date->getReviewTime(), $scheduled_transition->getTransitionTime());

    // Delete scheduled transition.
    $this->drupalGet('admin/scheduled-transition/1/delete');
    $this->submitForm([], 'Delete');
    drupal_flush_all_caches();
    $scheduled_transition = ScheduledTransition::load(1);
    $this->assertNull($scheduled_transition);
    $review_date = ReviewDate::getActiveReviewDate($node, $langcode);
    $this->assertNull($review_date->getScheduledTransition());

    // Check new scheduled transition is created.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $next_review = date('Y-m-d', strtotime('+6 months'));
    $edit = [
      'localgov_review_date[0][reviewed]' => TRUE,
      'localgov_review_date[0][review][review_date]' => $next_review,
    ];
    $this->submitForm($edit, 'Save');
    $review_date = ReviewDate::getActiveReviewDate($node, $langcode);
    $this->assertEquals(3, $review_date->id());
    $this->assertEquals(strtotime($next_review), $review_date->getReviewTime());
    $scheduled_transition = $review_date->getScheduledTransition();
    $this->assertEquals(2, $scheduled_transition->id());
    $this->assertEquals($review_date->getReviewTime(), $scheduled_transition->getTransitionTime());
  }

}
