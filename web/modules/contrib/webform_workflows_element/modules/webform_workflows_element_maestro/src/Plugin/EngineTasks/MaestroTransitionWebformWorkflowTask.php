<?php

namespace Drupal\webform_workflows_element_maestro\Plugin\EngineTasks;

use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\Plugin\EngineTasks\MaestroInteractiveTask;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_workflows_element\Element\WebformWorkflowsElement;
use Drupal\webform_workflows_element_maestro\Plugin\EngineTasks\MaestroWebformWorkflowsTrait;
use Drupal\workflow\Entity\WorkflowTransition;

/**
 * Require user to transition the submission.
 *
 * @Plugin(
 *   id = "MaestroTransitionWebformWorkflowTask",
 *   task_description = @Translation("Require user to transition the submission."),
 * )
 */
class MaestroTransitionWebformWorkflowTask extends MaestroInteractiveTask implements MaestroEngineTaskInterface {

  use MaestroWebformWorkflowsTrait;

  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return $this->t('Webform workflow transition');
    // If the task name is too long, you could abbreviate it here and use
    // in a template builder UI.
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    // Same as shortDescription, but just longer!  (if need be obviously)
    return $this->t('Webform workflow transition');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroTransitionWebformWorkflowTask';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#b17ce1';
  }

  /**
   * Part of the ExecutableInterface.
   *
   * Execution of the Example task returns TRUE and does nothing else.
   *
   * {@inheritdoc}.
   */
  public function execute() {
    return parent::execute();
  }

  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    $queueID = $this->queueID;

    $form['queueID'] = [
      '#type' => 'hidden',
      '#title' => 'Hidden Queue ID',
      '#default_value' => $queueID,
      '#description' => ('queueID'),
    ];

    $submission = static::getSubmission($queueID);

    $form['submission_id'] = [
      '#type' => 'hidden',
      '#title' => 'Submission ID',
      '#default_value' => $submission->id(),
      '#description' => ('submission_id'),
    ];

    // Get rendered submission:
    $entity = \Drupal::entityTypeManager()->getStorage('webform_submission')->load($submission->id());
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $pre_render = $view_builder->view($entity, 'full');
    $render_output = \Drupal::service('renderer')->render($pre_render);

    $form['submission'] = [
      '#type' => 'markup',
      '#markup' => $render_output,
    ];

    $workflowsManager = \Drupal::service('webform_workflows_element.manager');
    $workflowElements = $workflowsManager->getWorkflowElementsForWebform($submission->getWebform());
    $form['actions'] = [];
    foreach ($workflowElements as $elementId => $workflowElement) {
      $elementValue = $submission->getElementData($elementId);
      $workflowElement['#value']['workflow_state'] = $elementValue['workflow_state'];
      $transitions = WebformWorkflowsElement::getAvailableTransitions($workflowElement, TRUE);

      if ($workflowElement['#log_public_setting'] != 'Disabled') {
        $form[$elementId . ':log_public'] = [
          '#title'    => t('@workflowName: log message for submitter', ['@workflowName' => $workflowElement['#title']]),
          '#type'     => 'textarea',
          '#rows'     => 2,
          '#required' => $workflowElement['#log_public_setting'] === 'Required',
        ];
      }

      if ($workflowElement['#log_admin_setting'] != 'Disabled') {
        $form[$elementId . ':log_admin'] = [
          '#title'    => t('@workflowName: log message - admin only', ['@workflowName' => $workflowElement['#title']]),
          '#type'     => 'textarea',
          '#rows'     => 2,
          '#required' => $workflowElement['#log_admin_setting'] === 'Required',
        ];
      }

      foreach ($transitions as $transition) {
        $id = $elementId . ':' . $transition->id();
        if (count($workflowElements) > 1) {
          $title = $workflowElement['#title'] . ': ' . $transition->label();
        } else {
          $title = $transition->label();
        }

        $form['actions'][$id] = [
          '#type' => 'submit',
          '#value' => $title,
        ];
        if ($modal == 'modal') {
          $form['actions'][$id]['#ajax'] = [
            'callback' => [$parent, 'completeForm'],
            'wrapper' => '',
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
    $queueID = intval($form_state->getValue('maestro_queue_id'));

    $canExecute = MaestroEngine::canUserExecuteTask($queueID, \Drupal::currentUser()->id());

    if ($queueID > 0 && $canExecute) {
      $submission = static::getSubmission($queueID);

      $triggeringElement = $form_state->getTriggeringElement();
      $formAction = $triggeringElement['#parents'][0];
      $split = explode(':', $formAction);
      $workflowElementId = $split[0];
      $transition = $split[1];

      $workflowValue = $submission->getElementData($workflowElementId);
      $workflowValue['transition'] = $transition;
      if ($log_public = $form_state->getValue($workflowElementId . ':' . 'log_public')) {
        $workflowValue['log_public'] = $log_public;
      }
      if ($log_admin = $form_state->getValue($workflowElementId . ':' . 'log_admin')) {
        $workflowValue['log_admin'] = $log_admin;
      }
      $submission->setElementData($workflowElementId, $workflowValue);
      $submission->save();

      MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
    }

    $task = MaestroEngine::getTemplateTaskByQueueID($queueID);
    if (isset($task['data']['redirect_to'])) {
      $response = new TrustedRedirectResponse($task['data']['redirect_to']);
      $form_state->setResponse($response);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    $form = parent::getTaskEditForm($task, $templateMachineName);
    $form['#markup'] = 'Edit workflow transition task';

    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text for task console'),
      '#description' => $this->t("Shown on the user's task console."),
      '#default_value' => isset($task['data']['button_text']) ? $task['data']['button_text'] : t('Review and transition through workflow'),
      '#required' => TRUE,
    ];

    unset($form['handler']);
    unset($form['modal']);
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
    /*
     * Need to validate anything on your edit form?  Do that here.
     */
  }

  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    parent::prepareTaskForSave($form, $form_state, $task);

    // Override core settings:
    unset($task['data']['handler']);
    $task['data']['modal'] = 'notmodal';

    $button_text = $form_state->getValue('button_text');
    if (isset($button_text)) {
      $task['data']['button_text'] = $button_text;
    } else {
      $task['data']['button_text'] = t('Review and transition through workflow');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
  }
}
