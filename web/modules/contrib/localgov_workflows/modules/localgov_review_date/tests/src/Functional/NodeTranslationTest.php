<?php

namespace Drupal\Tests\localgov_review_date\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\localgov_review_date\Entity\ReviewDate;
use Drupal\scheduled_transitions\Form\ScheduledTransitionsSettingsForm;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test ReviewDates works with translated content.
 */
class NodeTranslationTest extends BrowserTestBase {

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
    'content_translation',
    'language',
    'locale',
    'localgov_review_date',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Create a content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'title' => 'Page',
    ]);

    // Enable workflow for content.
    $workflow = Workflow::load('localgov_editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

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

    // Enable Welsh for page.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm([
      'predefined_langcode' => 'cy',
    ], 'Add language');
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm([
      'entity_types[node]' => TRUE,
      'settings[node][page][translatable]' => TRUE,
      'settings[node][page][settings][language][language_alterable]' => TRUE,
    ], 'Save configuration');
    $this->rebuildContainer();
  }

  /**
   * Test translated content.
   */
  public function testTranslatedContent() {

    // Review a page in English.
    $en_title = $this->randomString(12);
    $en_review_time = date('Y-m-d', strtotime('+3 months'));
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => $en_title,
      'langcode[0][value]' => 'en',
      'localgov_review_date[0][reviewed]' => TRUE,
      'localgov_review_date[0][review][review_date]' => $en_review_time,
    ], 'Save');
    $node = $this->drupalGetNodeByTitle($en_title);
    $en_review_date = ReviewDate::getActiveReviewDate($node, 'en');
    $this->assertEquals(strtotime($en_review_time), $en_review_date->getReviewTime());

    // Review a Welsh translation.
    $cy_title = $this->randomString(12);
    $cy_review_time = date('Y-m-d', strtotime('+6 months'));
    $this->drupalGet('node/' . $node->id() . '/translations');
    $this->clickLink('Add');
    $this->submitForm([
      'title[0][value]' => $cy_title,
      'localgov_review_date[0][reviewed]' => TRUE,
      'localgov_review_date[0][review][review_date]' => $cy_review_time,
    ], 'Save (this translation)');
    $cy_review_date = ReviewDate::getActiveReviewDate($node, 'cy');
    $this->assertEquals(strtotime($cy_review_time), $cy_review_date->getReviewTime());
    $this->assertNotEquals($en_review_date->id(), $cy_review_date->id());

    // Re-review the Welsh translation.
    $this->drupalGet('cy/node/' . $node->id() . '/edit');
    $cy_rereview_time = date('Y-m-d', strtotime('+12 months'));
    $this->submitForm([
      'localgov_review_date[0][reviewed]' => TRUE,
      'localgov_review_date[0][review][review_date]' => $cy_rereview_time,
    ], 'Save (this translation)');
    $cy_rereview_date = ReviewDate::getActiveReviewDate($node, 'cy');
    $this->assertInstanceOf('\Drupal\localgov_review_date\Entity\ReviewDate', $cy_rereview_date);
    $this->assertEquals(strtotime($cy_rereview_time), $cy_rereview_date->getReviewTime());
    $this->assertNotEquals($cy_review_date->id(), $cy_rereview_date->id());
    $this->assertNotEquals($en_review_date->id(), $cy_rereview_date->id());
  }

}
