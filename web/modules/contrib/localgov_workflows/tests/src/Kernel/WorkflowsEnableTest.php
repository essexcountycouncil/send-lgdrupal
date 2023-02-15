<?php

namespace Drupal\Tests\localgov_workflows\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Kernel test enabling LocalGov Workflows.
 *
 * @group localgov_workflows
 */
class WorkflowsEnableTest extends KernelTestBase {

  use ContentTypeCreationTrait;

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
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('scheduled_transition');
    $this->installEntitySchema('workflow');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'content_moderation',
      'filter',
      'node',
      'scheduled_transitions',
      'system',
      'views',
    ]);
  }

  /**
   * Test scheduled transition configuration on enabling workflow.
   */
  public function testScheduledTransitions() {

    // Create a content type and configure for scheduled transitions.
    $this->createContentType([
      'type' => 'page',
      'title' => 'Page',
    ]);
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
      'scheduled_transition_settings',
      'config:scheduled_transitions.settings',
    ]);
    $configured_bundles = \Drupal::service('config.factory')->get('scheduled_transitions.settings')->get('bundles');
    $this->assertEquals($bundles, $configured_bundles);

    // Create a new content type and enable LocalGov Workflows.
    $this->createContentType([
      'type' => 'localgov_page',
      'title' => 'Page',
    ]);
    \Drupal::service('module_installer')->install(['localgov_workflows']);

    // Check scheduled transitions config is correct.
    $expected_bundles = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'localgov_page',
      ],
    ];
    $configured_bundles = \Drupal::service('config.factory')->get('scheduled_transitions.settings')->get('bundles');
    $this->assertEquals($expected_bundles, $configured_bundles);
  }

}
