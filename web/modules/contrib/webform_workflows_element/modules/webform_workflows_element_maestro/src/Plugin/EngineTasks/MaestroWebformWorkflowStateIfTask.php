<?php

namespace Drupal\webform_workflows_element_maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\Plugin\EngineTasks\MaestroIfTask;
use Drupal\webform\Entity\Webform;
use Drupal\webform_workflows_element_maestro\Plugin\EngineTasks\MaestroWebformWorkflowsTrait;

/**
 * @Plugin(
 *   id = "MaestroWebformWorkflowStateIfTask",
 *   task_description = @Translation("Webform workflow - if submission state"),
 * )
 */
class MaestroWebformWorkflowStateIfTask extends MaestroIfTask implements MaestroEngineTaskInterface {

  use MaestroWebformWorkflowsTrait;

  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return t('Webform workflow IF at state');
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Webform workflow IF at a specified state.');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroWebformWorkflowStateIfTask';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#daa520';
  }

  /**
   * Part of the ExecutableInterface
   * Execution of the Batch Function task will use the handler for this task as the executable function.
   * {@inheritdoc}.
   */
  public function execute() {
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);

    $ifData = $task['data']['if'];

    $submission = static::getSubmission($this->queueID);
    $workflowsManager = \Drupal::service('webform_workflows_element.manager');
    $workflowElements = $workflowsManager->getWorkflowElementsForWebform($submission->getWebform());
    if (!isset($workflowElements[$ifData['workflow_element']])) {
      \Drupal::logger('webform_workflows_element_maestro')->error('Workflow element ID not a valid webform workflows element.');
      return FALSE;
    }

    $elementValue = $submission->getElementData($ifData['workflow_element']);

    // Test if current value matches what is being tested:
    $currentState = $elementValue['workflow_state'];
    if ($currentState == $ifData['workflow_state']) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
  }

  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    $ifParms = isset($task['data']['if']) ? $task['data']['if'] : [];

    $form = parent::getTaskEditForm($task, $templateMachineName);

    $form['#markup'] = $this->t('Edit the logic for this IF task');
    unset($form['method']);
    unset($form['byvariable']);
    unset($form['bystatus']);
    unset($form['bylasttaskstatus']);

    $form['workflow_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webform workflows element key'),
      '#description' => $this->t('machine name e.g. "workflow"'),
      '#default_value' => isset($ifParms['workflow_element']) ? $ifParms['workflow_element'] : 'workflow',
      '#required' => TRUE,
    ];

    $form['workflow_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workflow state ID to match'),
      '#description' => $this->t('machine name e.g. "approved"'),
      '#default_value' => isset($ifParms['workflow_state']) ? $ifParms['workflow_state'] : '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
    parent::validateTaskEditForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    $task['data']['if'] = [
      'method' => 'webform_workflows_element_state',
      'workflow_element' => $form_state->getValue('workflow_element'),
      'workflow_state' => $form_state->getValue('workflow_state'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    // We have a number of fields that we know MUST be filled in.
    // the issue is that we have a to and falseto branches that we really don't know if they should be connected or not
    // so for the time being, we'll leave the to and falseto branches alone.
    $data = $task['data']['if'];
    // First check the method.  if it's blank, the whole thing will simply fail out.

  }
}
