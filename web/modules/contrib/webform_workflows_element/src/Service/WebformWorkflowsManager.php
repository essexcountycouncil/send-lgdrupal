<?php

namespace Drupal\webform_workflows_element\Service;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_workflows_element\Element\WebformWorkflowsElement as ElementWebformWorkflowsElement;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\Transition;

/**
 * Class WebformWorkflowsManager.
 *
 * $workflowsManager = \Drupal::service('webform_workflows_element.manager');
 */
class WebformWorkflowsManager {

  /**
   * Constructs a new object.
   */
  public function __construct() {
  }

  /**
   * Get states for an element's workflow.
   *
   * @param mixed $element
   *   Workflows element.
   *
   * @return array
   */
  public function getStatesFromElement($element): array {
    $workflowType = $this->getWorkflowTypeFromElement($element);
    return $workflowType->getStates();
  }

  /**
   * Get workflow type from workflow for element.
   *
   * @param array $element
   *   Workflows element.
   *
   * @return \Drupal\workflows\WorkflowTypeInterface
   *   Workflow type.
   */
  public function getWorkflowTypeFromElement(array $element) {
    return isset($element['#workflow']) ? $this->getWorkflowType($element['#workflow']) : NULL;
  }

  /**
   * Get workflow type for a workflow.
   *
   * @param string $workflowId
   *   String ID.
   *
   * @return \Drupal\workflows\WorkflowTypeInterface
   *   Workflow type.
   */
  public function getWorkflowType(string $workflowId) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load($workflowId);
    if (!$workflow) {
      return NULL;
    }
    /** @var \Drupal\webform_workflows_element\Plugin\WorkflowType\WebformWorkflowsElement $workflowType */
    $workflowType = $workflow->getTypePlugin();
    return $workflowType;
  }

  /**
   * Get the initial workflow state for the workflow of the element.
   *
   * @param array $element
   *   Workflows element.
   *
   * @return \Drupal\workflows\StateInterface
   *   The workflow state.
   */
  public function getInitialStateForElement(array $element): ?Drupal\workflows\StateInterface {
    $workflowType = $this->getWorkflowTypeFromElement($element);
    if ($workflowType) {
      // Load the default state for the workflow.
      if ($state = $workflowType->getInitialState()) {
        return $state;
      }
      else {
        // If no default state set, use the first one.
        $allStates = $workflowType->getStates();
        return reset($allStates);
      }
    }
    return NULL;
  }

  /**
   * Get all transitions for a current state for a workflow.
   *
   * Optionally also filter by user access.
   *
   * @param string $workflowId
   * @param string $currentStateId
   * @param \Drupal\Core\Session\AccountInterface|null $account
   * @param \Drupal\webform\WebformInterface|null $webform
   *
   * @return array
   *   Array of WorkflowTransitions.
   *
   * @throws \Exception
   */
  public function getAvailableTransitionsForWorkflow(string $workflowId, string $currentStateId, AccountInterface $account = NULL, WebformInterface $webform = NULL): array {
    $workflowType = $this->getWorkflowType($workflowId);
    if (!$workflowType) {
      return [];
    }

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load($workflowId);

    if ($currentStateId && $workflowType->hasState($currentStateId)) {
      $currentState = $workflowType->getState($currentStateId);
    }
    else {
      $currentState = $workflowType->getInitialState();
    }

    if (!$currentState) {
      return [];
    }

    return $this->getValidTransitions($workflow, $currentState, $account, $webform);
  }

  /**
   * @param $workflow
   * @param $state
   * @param $account
   * @param \Drupal\webform\Entity\Webform|NULL $webform
   *
   * @return array
   *   Array of WorkflowTransitions.
   *
   * @throws \Exception
   */
  public function getValidTransitions($workflow, $state, $account = NULL, Webform $webform = NULL): array {
    // Get available transitions from current state:
    $availableTransitions = $state->getTransitions();

    if ($webform && $account) {
      foreach ($availableTransitions as $transition_id => $transition) {
        $access = $this->checkAccessForSubmissionAndTransition($workflow, $account, $webform, $transition);
        if (!$access) {
          unset($availableTransitions[$transition_id]);
        }
      }
    }

    return $availableTransitions;
  }

  /**
   * Check if user can do a transition for a workflow for a webform.
   *
   * @param Workflow $workflow
   * @param AccountInterface $account
   * @param WebformInterface $webform
   * @param \Drupal\workflows\Transition $transition
   *
   * @return bool
   *   Whether user can transition the workflow.
   *
   * @throws \Exception
   */
  public function checkAccessForSubmissionAndTransition(Workflow $workflow, AccountInterface $account, WebformInterface $webform, Transition $transition): bool {
    $pass = FALSE;
    $workflow_elements = $this->getWorkflowElementsForWebform($webform);

    foreach ($workflow_elements as $element) {
      if ($element['#workflow'] != $workflow->id()) {
        continue;
      }

      $transition_access_id = 'access_transition_' . $transition->id();

      $rule_id = 'transition_' . $transition->id();
      if (isset($element['#' . $transition_access_id . '_workflow_enabled']) && !$element['#' . $transition_access_id . '_workflow_enabled']) {
        return FALSE;
      }
      $pass = $this->checkAccessForWorkflowAccessRules($element, $webform, $account, $transition_access_id, $rule_id);
    }

    return $pass;
  }

  /**
   * Get all workflow elements for a webform.
   *
   * @param WebformInterface $webform
   *   Webform.
   *
   * @return array
   *   Array of elements arrays.
   */
  public function getWorkflowElementsForWebform(WebformInterface $webform): array {
    $elements = $webform->getElementsOriginalDecoded();
    return $this->filterWorkflowElements($elements);
  }

  /**
   * Recursively filter webform elements to only keep webform_workflows_element.
   *
   * @param array $elements
   *   Webform elements returned by $webform->getElementsOriginalDecoded().
   *
   * @return array
   *   Filtered webform elements keyed by machine name.
   */
  protected function filterWorkflowElements(array $elements): array {
    $filtered = [];
    foreach (Element::children($elements) as $key) {
      $element = $elements[$key];
      if (isset($element['#type']) && $element['#type'] == 'webform_workflows_element') {
        $filtered[$key] = $element;
      }
      $filtered += $this->filterWorkflowElements($element);
    }
    return $filtered;
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Entity\EntityInterface $webform
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param string $access_id
   * @param string $rule_id
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function checkAccessForWorkflowAccessRules(array $element, EntityInterface $webform, AccountInterface $account, string $access_id, string $rule_id): bool {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = Drupal::service('plugin.manager.webform.element');

    $element_plugin = $element_manager->getElementInstance($element, $webform);

    $pass = $element_plugin->checkAccessRules($rule_id, $element, $account);

    if (isset($element[$access_id . '_group_roles']) && count($element[$access_id . '_group_roles']) > 0) {
      if (Drupal::moduleHandler()->moduleExists('webform_group')) {
        $group_access = webform_group_webform_element_access($rule_id, $element, $account);
      }
      elseif (Drupal::moduleHandler()
        ->moduleExists('webform_group_extended')) {
        $group_access = webform_group_extended_webform_element_access($rule_id, $element, $account);
      }
      else {
        $group_access = AccessResult::neutral();
      }
      if (!$group_access->isAllowed()) {
        $pass = FALSE;
      }
    }
    return $pass;
  }

  /**
   * Load webforms which have workflow elements
   *
   * @param string|null $workflow_id
   *
   * @return array of WebformInterface
   */
  public function getWebformsWithWorkflowElements(string $workflow_id = NULL): array {
    $webforms = [];
    $connection = Database::getConnection();
    $query = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('name', $connection->escapeLike('webform.webform.') . '%', 'LIKE')
      ->condition('data', '%' . $connection->escapeLike('webform_workflows_element') . '%', 'LIKE');

    if ($workflow_id) {
      $query->condition('data', '%' . $connection->escapeLike("'#workflow': " . $workflow_id) . '%', 'LIKE');
    }

    $webformsData = $query->execute()
      ->fetchAll();
    foreach ($webformsData as $data) {
      $data = unserialize($data->data);
      $webforms[] = Webform::load($data['id']);
    }
    return $webforms;
  }

  /**
   * Check if user can see workflow element.
   *
   * @param AccountInterface $account
   * @param WebformInterface $webform
   * @param array $workflow_element
   *
   * @return bool
   *   TRUE if access allowed.
   *
   * @throws \Exception
   */
  public function checkUserCanAccessElement(AccountInterface $account, WebformInterface $webform, array $workflow_element): bool {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = Drupal::service('plugin.manager.webform.element');

    $element_plugin = $element_manager->getElementInstance($workflow_element, $webform);

    if (!$element_plugin->checkAccessRules('update', $workflow_element, $account)) {
      return FALSE;
    }
    elseif (count(ElementWebformWorkflowsElement::getAvailableTransitions($workflow_element)) == 0) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get all transitions for a given workflow.
   *
   * This is not limited by what's possible for a given submission.
   *
   * @param string $workflow_id
   *   Workflow ID.
   *
   * @return array
   *   Array of transitions or NULL;
   */
  public function getTransitionsForWorkflow(string $workflow_id): ?array {
    $workflowType = $this->getWorkflowType($workflow_id);
    if (!$workflowType) {
      return NULL;
    }
    return $workflowType->getTransitions();
  }

  /**
   * Check if user has access to update a workflow element to a certain state.
   *
   * @param AccountInterface $account
   * @param WebformSubmissionInterface $webform_submission
   * @param array $element
   * @param string $state_id
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function checkAccessToUpdateBasedOnState(AccountInterface $account, WebformSubmissionInterface $webform_submission, array $element, string $state_id): Drupal\Core\Access\AccessResultInterface {
    $state = $this->getStateFromElementAndId($element, $state_id);
    if (!$state) {
      return AccessResult::neutral();
    }

    $state_access_id = 'access_update_at_state_' . $state->id();

    if (!isset($element['#' . $state_access_id . '_override']) || !$element['#' . $state_access_id . '_override']) {
      return AccessResult::neutral();
    }

    if (isset($element['#' . $state_access_id . '_workflow_enabled']) && !$element['#' . $state_access_id . '_workflow_enabled']) {
      return AccessResult::forbidden();
      //return $access_rules_manager->checkWebformSubmissionAccess('administer', $account, $webform_submission);
    }

    /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
    $access_rules_manager = Drupal::service('webform.access_rules_manager');
    return $access_rules_manager->checkWebformSubmissionAccess('update_at_state_' . $state->id(), $account, $webform_submission);
  }

  /**
   * Get state for an element with the state ID, or return NULL.
   *
   * @param mixed $element
   *   Workflows element.
   * @param mixed $id
   *   State ID.
   *
   * @return \Drupal\workflows\StateInterface
   *   The workflow state.
   */
  public function getStateFromElementAndId($element, $id): ?Drupal\workflows\StateInterface {
    $workflowType = $this->getWorkflowTypeFromElement($element);
    return $workflowType->hasState($id) ? $workflowType->getState($id) : NULL;
  }

  /**
   * Modify element values to reflect changes due to transition.
   *
   * Or return null to indicate no transition happened.
   *
   * Note this does not run the transition - these values need to be saved to
   * the element value of the specific submission in order to trigger that.
   *
   * @param array $element
   * @param string $element_id
   * @param WebformSubmissionInterface $webform_submission
   *
   * @return array|null
   *   Array of updated composite element values, or NULL.
   * @throws \Exception
   */
  public function runTransitionOnElementValue(array $element, string $element_id, WebformSubmissionInterface $webform_submission): ?array {
    $data = $webform_submission->getElementData($element_id);

    $newData = $data;

    $workflow = Workflow::load($element['#workflow']);
    if (!$workflow) {
      return NULL;
    }

    /** @var \Drupal\webform_workflows_element\Plugin\WorkflowType\WebformWorkflowsElement $workflowType */
    $workflowType = $this->getWorkflowType($element['#workflow']);
    if (!$workflowType) {
      return NULL;
    }

    // Set initial state if no workflow data set:
    if (!$data) {
      $initialState = $workflowType->getInitialState();
      if ($initialState) {
        $newData['workflow_state'] = $initialState->id();
        $newData['workflow_state_label'] = $initialState->label();
      }
      else {
        return NULL;
      }
    }

    // Check if any transitions set to run on edit if no transition set to change:
    if ($data && (!isset($data['transition']) || $data['transition'] == '')) {
      $transitions = $workflowType->getTransitionsForState($newData['workflow_state']);
      foreach ($transitions as $transition) {
        $runOnEditCondition = $element['#transition_' . $transition->id() . '_run_on_edit'] ?? FALSE;
        if ($runOnEditCondition) {
          $run = TRUE;

          // By default, anyone can access, unless it must be submission owner:
          if ($runOnEditCondition === 'owner') {
            $run = Drupal::currentUser()
                ->id() === $webform_submission->getOwnerId();
          }

          if ($run) {
            $data['transition'] = $transition->id();
          }
        }
      }
    }

    // Run transition if set:
    if ($data && isset($data['transition']) && $data['transition'] != '') {
      $transition = $workflowType->getTransition($data['transition']);

      if (!$transition) {
        $newData['transition'] = '';
        $webform_submission->setElementData($element_id, $newData);
        return NULL;
      }

      // Confirm access to submission and transition:
      $account = Drupal::currentUser();
      $access = $this->checkAccessForSubmissionAndTransition($workflow, $account, $webform_submission->getWebform(), $transition);
      if (!$access) {
        $newData['transition'] = '';
        $webform_submission->setElementData($element_id, $newData);
        return NULL;
      }

      // Set workflow state field value to the value of the new transition:
      $newData['workflow_state'] = $transition->to()->id();
      $newData['workflow_state_label'] = $transition->to()->label();

      // Update who changed and when:
      $newData['changed_user'] = Drupal::currentUser()->id();
      $newData['changed_timestamp'] = strtotime('now');
    }

    // Note: workflow_state_previous is set by default on the form.
    return $newData;
  }

  /**
   * Log transition if logging is enabled on the webform.
   *
   * @param array $element
   * @param string $element_id
   * @param WebformSubmissionInterface $webform_submission
   * @param array $elementData
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function logTransition(array $element, string $element_id, WebformSubmissionInterface $webform_submission, array $elementData) {
    if (!isset($elementData['transition']) || !$elementData['transition']) {
      return;
    }

    $webform = $webform_submission->getWebform();

    // Log webform submissions to the 'webform_submission' log.
    if (!$webform->hasSubmissionLog()) {
      return;
    }

    /** @var \Drupal\webform_workflows_element\Plugin\WorkflowType\WebformWorkflowsElement $workflowType */
    $workflowType = $this->getWorkflowType($element['#workflow']);

    if (!$workflowType) {
      return;
    }

    $transition = $workflowType->getTransition($elementData['transition']);

    $context = [
      'link' => ($webform_submission->id()) ? $webform_submission->toLink(t('Edit'), 'edit-form')
        ->toString() : NULL,
      'webform_submission' => $webform_submission,
      'operation' => 'workflow status changed',
      '@title' => $element['#title'],
      '@transition' => $transition->label(),
      '@state_old' => $elementData['workflow_state_label'],
      '@state_new' => $transition->to()->label(),
      '@log_admin' => isset($elementData['log_admin']) && $elementData['log_admin'] ? t('Admin log message: @log', ['@log' => $elementData['log_admin']]) : '',
      '@log_public' => isset($elementData['log_public']) && $elementData['log_public'] ? t('Public log message: @log', ['@log' => $elementData['log_public']]) : '',
      '@user_on_behalf_of' => '',

      /*
          We append some technical information for reference / searching.
          Sadly logger logs in Drupal aren't more fieldable.
          We store state id and plugin_id because the element or workflow could be modified later,
          which would make them not derivable from transition or element.

          This can be used as a unique ID to find the relevant log message for a workflow transition being run.
          */
      '@transition_id' => t('Technical reference: [workflow:@element_id:@workflow_plugin_id:@transition_id:@new_state_id:@old_state_id]', [
        '@element_id' => $element_id,
        '@workflow_plugin_id' => $workflowType->getPluginId(),
        '@transition_id' => $transition->id(),
        '@new_state_id' => $transition->to()->id(),
        '@old_state_id' => $elementData['workflow_state'],
      ]),
    ];

    $message = '@title: transition "@transition" - status changed from "@state_old" to "@state_new".';
    $message .= (isset($elementData['log_public']) && $elementData['log_public'] ? '<br><br>@log_public' : '');
    $message .= (isset($elementData['log_admin']) && $elementData['log_admin'] ? '<br><br>@log_admin' : '');
    $message .= '<br><br>@transition_id';

    Drupal::logger('webform_submission')->notice($message, $context);
  }

}
