<?php

namespace Drupal\Tests\localgov_review_date\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;
use Drupal\scheduled_transitions\Form\ScheduledTransitionsSettingsForm;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the LocalGov Review Status admin UI.
 *
 * @group path
 */
class AdminSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_review_date',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

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

    // Create test user and log in.
    $web_user = $this->drupalCreateUser([
      'create page content',
      'administer localgov_review_date',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the review status admin settings.
   */
  public function testAdminSettings() {
    $assert_session = $this->assertSession();

    // Check the default next review date is 12 months.
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->assertSession()->optionExists('localgov_review_date[0][review][review_in]', 12)->isSelected());
    $next_review_date = date('Y-m-d', strtotime('+12 months'));
    $assert_session->fieldValueEquals('localgov_review_date[0][review][review_date]', $next_review_date);

    // Check default review date options.
    $this->drupalGet('admin/config/workflow/localgov-review-date');
    $this->assertSession()->optionExists('default_next_review', 3);
    $this->assertSession()->optionExists('default_next_review', 6);
    $this->assertSession()->optionExists('default_next_review', 12);
    $this->assertSession()->optionExists('default_next_review', 18);
    $this->assertSession()->optionExists('default_next_review', 24);
    $this->assertSession()->optionExists('default_next_review', 36);

    // Change default review date.
    $default_next_review = 3;
    $edit = [
      'default_next_review' => $default_next_review ,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check new default review date.
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->assertSession()->optionExists('localgov_review_date[0][review][review_in]', $default_next_review)->isSelected());
    $next_review_date = date('Y-m-d', strtotime('+' . $default_next_review . ' months'));
    $assert_session->fieldValueEquals('localgov_review_date[0][review][review_date]', $next_review_date);
  }

}
