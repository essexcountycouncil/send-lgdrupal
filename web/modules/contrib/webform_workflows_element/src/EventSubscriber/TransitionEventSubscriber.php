<?php

namespace Drupal\webform_workflows_element\EventSubscriber;

use Drupal;
use Drupal\webform_workflows_element\Event\WebformSubmissionWorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TransitionEventSubscriber.
 *
 * @package Drupal\webform_workflows_element\EventSubscriber
 */
class TransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      WebformSubmissionWorkflowTransitionEvent::EVENT_NAME => 'onTransition',
    ];
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param \Drupal\webform_workflows_element\Event\WebformSubmissionWorkflowTransitionEvent $event
   *   Event object.
   */
  public function onTransition(WebformSubmissionWorkflowTransitionEvent $event) {
    $workflowsManager = Drupal::service('webform_workflows_element.manager');

    // Load key things:
    $webform_submission = $event->submission;
    $element = $webform_submission->getWebform()
      ->getElementDecoded($event->element_id);
    $elementData = $webform_submission->getElementData($event->element_id);

    // Log the transition if necessary/possible:
    $workflowsManager->logTransition($element, $event->element_id, $webform_submission, $elementData, $event->originalElementData);

  }

}
