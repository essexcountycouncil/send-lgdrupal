<?php

namespace Drupal\Tests\localgov_review_date\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_review_date\Entity\ReviewDate;
use Drupal\scheduled_transitions\Entity\ScheduledTransition;
use Drupal\scheduled_transitions\Form\ScheduledTransitionsSettingsForm;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests loading and storing data using the ReviewSDate entity.
 *
 * @group path
 */
class ReviewDateEntityTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_moderation',
    'dynamic_entity_reference',
    'field',
    'filter',
    'node',
    'scheduled_transitions',
    'system',
    'text',
    'user',
    'views',
    'workflows',
    'localgov_workflows',
    'localgov_review_date',
  ];

  /**
   * Node to review.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setup();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('scheduled_transition');
    $this->installEntitySchema('workflow');
    $this->installEntitySchema('review_date');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'content_moderation',
      'filter',
      'node',
      'scheduled_transitions',
      'system',
      'views',
      'localgov_workflows',
    ]);

    // Create a content type.
    $this->createContentType([
      'type' => 'page',
      'title' => 'Page',
    ]);

    // Add to workflow.
    $workflow = Workflow::load('localgov_editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Add to scheduled transitions.
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

    // Create a node.
    $this->node = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(12),
    ]);
  }

  /**
   * Tests ReviewDateInterface.
   */
  public function testReviewDateEntities() {

    // Create a scheduled transition.
    $reviewed = (new \DateTime('1 Jan 2020 12am'))->getTimestamp();
    $scheduled_transition = ScheduledTransition::create([
      'entity' => $this->node,
      'entity_revision_id' => 1,
      'author' => 1,
      'workflow' => 'localgov_editorial',
      'moderation_state' => 'published',
      'transition_on' => $reviewed,
    ]);
    $scheduled_transition->save();

    // Create a review date and confirm interface methods work as expected.
    $review_date = ReviewDate::newReviewDate($this->node, 'en', $scheduled_transition);
    $this->assertTrue($review_date->isActive());
    $this->assertEquals($this->node->id(), $review_date->getEntity()->id());
    $this->assertEquals('en', $review_date->getLanguage());
    $this->assertEquals($reviewed, $review_date->getReviewTime());
    $this->assertEquals($scheduled_transition->id(), $review_date->getScheduledTransition()->id());

    // Check updating scheduled transition.
    $reviewed2 = (new \DateTime('1 Jan 2021 12am'))->getTimestamp();
    $scheduled_transition2 = ScheduledTransition::create([
      'entity' => $this->node,
      'entity_revision_id' => 1,
      'author' => 1,
      'workflow' => 'localgov_editorial',
      'moderation_state' => 'published',
      'transition_on' => $reviewed2,
    ]);
    $scheduled_transition2->save();
    $review_date->setScheduledTransition($scheduled_transition2);
    $this->assertEquals($reviewed2, $review_date->getReviewTime());
    $this->assertEquals($scheduled_transition2->id(), $review_date->getScheduledTransition()->id());
  }

  /**
   * Test active review date.
   */
  public function testActiveReviewDates() {

    // Create a scheduled transition.
    $reviewed = (new \DateTime('1 Jan 2020 12am'))->getTimestamp();
    $scheduled_transition = ScheduledTransition::create([
      'entity' => $this->node,
      'entity_revision_id' => 1,
      'author' => 1,
      'workflow' => 'localgov_editorial',
      'moderation_state' => 'published',
      'transition_on' => $reviewed,
    ]);
    $scheduled_transition->save();

    // Check active review dates are set on creation.
    $rd_en1 = ReviewDate::newReviewDate($this->node, 'en', $scheduled_transition);
    $rd_en1->save();
    $rd_cy1 = ReviewDate::newReviewDate($this->node, 'cy', $scheduled_transition);
    $rd_cy1->save();
    $this->assertEquals($rd_en1->id(), ReviewDate::getActiveReviewDate($this->node, 'en')->id());
    $this->assertEquals($rd_cy1->id(), ReviewDate::getActiveReviewDate($this->node, 'cy')->id());
    $this->assertNotEquals($rd_cy1->id(), $rd_en1->id());

    // Check active review date updates when reviewing content again.
    $rd_en2 = ReviewDate::newReviewDate($this->node, 'en', $scheduled_transition);
    $rd_en2->save();
    $rd_cy2 = ReviewDate::newReviewDate($this->node, 'cy', $scheduled_transition);
    $rd_cy2->save();
    $this->assertEquals($rd_en2->id(), ReviewDate::getActiveReviewDate($this->node, 'en')->id());
    $this->assertEquals($rd_cy2->id(), ReviewDate::getActiveReviewDate($this->node, 'cy')->id());
    $this->assertNotEquals($rd_cy2->id(), $rd_en2->id());
    $this->assertNotEquals($rd_en1->id(), $rd_en2->id());
    $this->assertNotEquals($rd_cy1->id(), $rd_cy2->id());
  }

}
