<?php

namespace Drupal\webform_workflows_element\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission action handler.
 *
 * @WebformHandler(
 *   id = "workflows_transition_email",
 *   label = @Translation("E-mail on workflow state change"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends an email when a submission status changes."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class StateChangeEmailWebformHandler extends EmailWebformHandler {

  /**
   * Get configuration default values.
   *
   * @return array
   *   Configuration default values.
   */
  protected function getDefaultConfigurationValues() {
    $this->defaultValues = parent::getDefaultConfigurationValues();
    $this->defaultValues['states'] = [];
    return $this->defaultValues;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // dd($form);

    $workflowsManager = \Drupal::service('webform_workflows_element.manager');
    $options = [];

    // Load all available transitions on the form per workflow element
    $workflow_elements = $workflowsManager->getWorkflowElementsForWebform($this->webform);
    foreach ($workflow_elements as $element_id => $element) {
      $transitions = $workflowsManager->getTransitionsForWorkflow($element['#workflow']);

      foreach ($transitions as $transition) {
        $options[$element_id . ':' . $transition->id()] = $this->t('…when submission transitions through <b>"@label"</b> to <b>"@state"</b>. <i>[@element]</i>', [
          '@label' => $transition->label(),
          '@state' => $transition->to()->label(),
          '@element' => $element['#title'],
        ]);
      }
    }
    $form['additional']['states']['#options'] = $options;

    $form['states_container'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Triggers to send handler'),
      'states' => $form['additional']['states'],
      '#weight' => -10,
    ];

    unset($form['additional']['states']);

    // @todo add the old state, the new state, and the log message as tokens
    // See https://opensenselabs.com/blogs/tech/how-create-custom-token-drupal-8

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Override parent class postSave entirely:
    if (!isset($this->configuration['states'])) {
      return FALSE;
    }

    $workflowsManager = \Drupal::service('webform_workflows_element.manager');
    $workflow_elements = $workflowsManager->getWorkflowElementsForWebform($this->webform);

    foreach ($workflow_elements as $element_id => $element) {
      $data = $webform_submission->getElementData($element_id);

      // Send e-mail if running a transition:
      if (isset($data['transition']) && $data['transition'] != '' && in_array($element_id . ':' . $data['transition'], $this->configuration['states'])) {
        $originalState = $data['workflow_state_previous'];
        $changedState = $data['workflow_state'];
        if ($originalState != $changedState) {
          $message = $this->getMessage($webform_submission);
          $this->sendMessage($webform_submission, $message);
        }
      }
    }

    if (isset($data['transition'])) {
      \Drupal::logger('webform_workflows_element')->debug('Sending e-mail for workflow transition ' . $data['transition']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBodyDefaultValues($format = NULL) {
    $webform_workflows_element_settings = $this->configFactory->get('webform_workflows_element.settings');
    $formats = [
      'text' => $webform_workflows_element_settings->get('mail.default_body_text') ?: NULL,
      'html' => $webform_workflows_element_settings->get('mail.default_body_html') ?: NULL,
    ];

    // Use non-workflow if not set for workflow:
    if (!$formats['text']) {
      return parent::getBodyDefaultValues($format);
    }

    return ($format === NULL) ? $formats : $formats[$format];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();
    $settings = $summary['#settings'];
    $settings['states'] = [];

    $states = $this->getEmailConfiguration()['states'];

    $workflowsManager = \Drupal::service('webform_workflows_element.manager');
    $workflow_elements = $workflowsManager->getWorkflowElementsForWebform($this->webform);
    foreach ($workflow_elements as $element_id => $element) {
      /** @var \Drupal\webform_workflows_element\Plugin\WorkflowType\WebformWorkflowsElement $workflowType */
      $workflowType = $workflowsManager->getWorkflowType($element['#workflow']);
      foreach ($states as $state) {
        $exploded = explode(':', $state);
        if ($exploded[0] != $element_id) {
          continue;
        }
        $transition_id = $exploded[1];
        if (!$workflowType->hasTransition($transition_id)) {
          continue;
        }
        $transition = $workflowType->getTransition($transition_id);
        $settings['states'][$transition_id] = $transition->label();
      }
    }

    $summary['#settings'] = $settings;
    return $summary;
  }
}
