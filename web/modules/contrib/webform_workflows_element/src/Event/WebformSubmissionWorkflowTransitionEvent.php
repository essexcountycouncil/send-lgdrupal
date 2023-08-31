<?php

namespace Drupal\webform_workflows_element\Event;

use Drupal;
use Drupal\Component\EventDispatcher\Event;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * Event that is fired when a user logs in.
 */
class WebformSubmissionWorkflowTransitionEvent extends Event {

  const EVENT_NAME = 'webform_submission_workflow_transition';

  /**
   * The submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  public WebformSubmissionInterface $submission;

  /**
   * The transition.
   *
   * @var \Drupal\workflows\TransitionInterface|NULL
   */
  public ?TransitionInterface $transition;

  /**
   * The webform element ID.
   *
   * @var string
   */
  public string $element_id;

  /*
   * Workflow element values from before transition was run.
   */
  public array $originalElementData;

  /**
   * Constructs the object.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission.
   */
  public function __construct(WebformSubmissionInterface $submission, string $element_id, array $originalElementData = NULL) {
    $this->submission = $submission;
    $this->element_id = $element_id;
    $this->originalElementData = $originalElementData;

    $this->transition = $this->getTransition();
  }

  /**
   * Get the workflow type in use.
   *
   * @return \Drupal\workflows\WorkflowTypeInterface
   */
  public function getWorkflowType(): ?WorkflowTypeInterface {
    $workflowsManager = Drupal::service('webform_workflows_element.manager');

    $webform = $this->submission->getWebform();
    $workflow_id = $webform->getElementDecoded($this->element_id)['#workflow'];
    return $workflowsManager->getWorkflowType($workflow_id);
  }

  /**
   * Get the transition that was run.
   *
   * @return TransitionInterface|NULL
   */
  public function getTransition(): ?TransitionInterface {
    $workflowType = $this->getWorkflowType();

    if (!$workflowType) {
      return NULL;
    }

    $values = $this->submission->getElementData($this->element_id);
    $transition_id = $values['transition'] ?? NULL;
    if (!$transition_id) {
      return NULL;
    }

    return $workflowType->getTransition($transition_id);
  }

}
