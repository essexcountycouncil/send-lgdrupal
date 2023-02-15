<?php

declare(strict_types = 1);

namespace Drupal\Tests\scheduled_transitions\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\scheduled_transitions\Routing\ScheduledTransitionsRouteProvider;
use Drupal\scheduled_transitions_test\Entity\ScheduledTransitionsTestEntity;
use Drupal\scheduled_transitions\ScheduledTransitionsPermissions as Permissions;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\scheduled_transitions\Traits\ScheduledTransitionTestTrait;

/**
 * Tests the route to add a new transition to an entity (modal form).
 *
 * @group scheduled_transitions
 * @coversDefaultClass \Drupal\scheduled_transitions\Form\Entity\ScheduledTransitionAddForm
 */
class ScheduledTransitionModalFormJavascriptTest extends WebDriverTestBase {

  use ContentModerationTestTrait;
  use ScheduledTransitionTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test_revlog',
    'scheduled_transitions_target_revisions_test',
    'scheduled_transitions_test',
    'scheduled_transitions',
    'content_moderation',
    'workflows',
    'dynamic_entity_reference',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests revision logs.
   */
  public function testRevisionLogOverride(): void {
    // Enable users to override messages.
    \Drupal::configFactory()->getEditable('scheduled_transitions.settings')
      ->set('message_override', TRUE)
      ->save(TRUE);

    $this->enabledBundles([['st_entity_test', 'st_entity_test']]);

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('st_entity_test', 'st_entity_test');
    $workflow->save();

    $currentUser = $this->drupalCreateUser([
      'administer st_entity_test entities',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
      Permissions::addScheduledTransitionsPermission('st_entity_test', 'st_entity_test'),
    ]);
    $this->drupalLogin($currentUser);

    $entity = ScheduledTransitionsTestEntity::create(['type' => 'st_entity_test']);
    $entity->name = 'revision 1';
    $entity->save();
    $entity->name = 'revision 2';
    $entity->setNewRevision(TRUE);
    $entity->save();
    $entity->name = 'revision 3';
    $entity->setNewRevision(TRUE);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    // Access the modal directly.
    $this->drupalGet($entity->toUrl(ScheduledTransitionsRouteProvider::LINK_TEMPLATE_ADD));

    // Open the details element.
    $this->getSession()->getPage()->find('css', '#edit-revision-metadata-revision-log > summary')->click();
    // The default revision log is shown.
    $this->assertSession()->pageTextContains('Scheduled transition: transitioning latest revision from Draft to - Unknown state -');

    $this->getSession()->getPage()->fillField('transition', 'archive');
    $this->getSession()->getPage()->pressButton('Reload preview');

    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Scheduled transition: transitioning latest revision from Draft to Archived');

    $this->getSession()->getPage()->fillField('revision', 2);
    $this->getSession()->getPage()->checkField('revision_metadata[revision_log][override]');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('revision_metadata[revision_log][custom][message]', 'Test message. Transitioning from revision #[scheduled-transitions:from-revision-id].');
    $this->getSession()->getPage()->pressButton('Reload preview');

    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Test message. Transitioning from revision #2');
  }

}
