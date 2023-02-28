<?php

namespace Drupal\Tests\localgov_review_date\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\scheduled_transitions\Form\ScheduledTransitionsSettingsForm;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the Review Status widget UI.
 *
 * @group path
 */
class ReviewDateWidgetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

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

    // Configure scheduled transitions.
    $scheduled_transitions_config = \Drupal::service('config.factory')->getEditable('scheduled_transitions.settings');
    $bundles = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
      ],
    ];
    $scheduled_transitions_config->set('bundles', $bundles);
    $scheduled_transitions_config->save();
    Cache::invalidateTags([
      ScheduledTransitionsSettingsForm::SETTINGS_TAG,
      'config:scheduled_transitions.settings',
    ]);

    $this->drupalLogin($this->rootUser);
  }

// @codingStandardsIgnoreStart
//   /**
//    * Test review date widget actions.
//    * This test currently fails intermittently due to timezone issues.
//    * See https://github.com/localgovdrupal/localgov_workflows/pull/18  
//    */
//   public function testReviewDateWidget() {

//     // Set timezone to UTC.
//     $this->drupalGet('admin/config/regional/settings');
//     $this->submitForm(['date_default_timezone' => 'UTC'], 'Save configuration');

//     // Check initial settings.
//     $this->drupalGet('node/add/page');
//     $page = $this->getSession()->getPage();
//     $page->hasUncheckedField('localgov_review_date[0][reviewed]');
//     $container = $page->find('css', '.review-date-container');
//     $this->assertNotTrue($container->isVisible());

//     // Check display when once checked.
//     $page->checkField('localgov_review_date[0][reviewed]');
//     $this->assertTrue($container->isVisible());

//     // Check next review date selector.
//     foreach (ReviewDateSettingsForm::getNextReviewOptions() as $month => $description) {
//       $review_in = date('Y-m-d', strtotime('+' . $month . ' month'));
//       $page->selectFieldOption('localgov_review_date[0][review][review_in]', $month);
//       $review_date = $page->findField('localgov_review_date[0][review][review_date]');
//       $this->assertEquals($review_in, $review_date->getValue());
//     }
//   }
// @codingStandardsIgnoreEnd

  /**
   * Test review date summary text.
   */
  public function testReviewDateSummaryText() {
    $assert_session = $this->assertSession();

    // Check not reviewed text.
    $this->drupalGet('node/add/page');
    $assert_session->elementContains('css', '.review-date-form summary', 'Not reviewed yet');
    $title = $this->randomMachineName();
    $this->submitForm(['title[0][value]' => $title], 'Save');
    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert_session->elementContains('css', '.review-date-form summary', 'Not reviewed yet');

    // Check the review date text.
    $page = $this->getSession()->getPage();
    $page->checkField('localgov_review_date[0][reviewed]');
    $page->findButton('Save')->click();
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert_session->elementNotContains('css', '.review-date-form summary', 'Not reviewed yet');
    $last_reviewed = date('Y-m-d');
    $next_review = date('Y-m-d', strtotime('+12 months'));
    $assert_session->elementContains('css', '.review-date-form summary', $last_reviewed);
    $assert_session->elementContains('css', '.review-date-form summary', $next_review);
  }

  /**
   * Test publishing.
   */
  public function testReviewDatePublish() {

    // Check initial settings.
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->hasUncheckedField('localgov_review_date[0][reviewed]');
    $container = $page->find('css', '.review-date-container');
    $this->assertNotTrue($container->isVisible());

    // Check changing content to published.
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->hasCheckedField('localgov_review_date[0][reviewed]');
    $container = $page->find('css', '.review-date-container');
    $this->assertTrue($container->isVisible());
  }

}
