<?php

namespace Drupal\webform_workflows_element\Service;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformAccessRulesManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_workflows_element\Element\WebformWorkflowsElement as ElementWebformWorkflowsElement;
use Drupal\webform_workflows_element\Event\WebformSubmissionWorkflowTransition;
use Drupal\webform_workflows_element\Event\WebformSubmissionWorkflowTransitionEvent;
use Drupal\webform_workflows_element\Plugin\WorkflowType\WebformWorkflowsElement;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\StateInterface;
use Drupal\workflows\Transition;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;
use Exception;

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
  public function getStatesFromElement(array $element): array {
    $workflowType = $this->getWorkflowTypeFromElement($element);
    if (!$workflowType) {
      return [];
    }

    return $workflowType->getStates();
  }

  /**
   * Get workflow type from workflow for element.
   *
   * @param array $element
   *   Workflows element.
   *
   * @return WorkflowTypeInterface
   *   Workflow type.
   */
  public function getWorkflowTypeFromElement(array $element): ?WorkflowTypeInterface {
    return isset($element['#workflow']) ? $this->getWorkflowType($element['#workflow']) : NULL;
  }

  /**
   * Get workflow type for a workflow.
   *
   * @param string $workflowId
   *   String ID.
   *
   * @return WorkflowTypeInterface|null
   *   Workflow type.
   */
  public function getWorkflowType(string $workflowId): ?WorkflowTypeInterface {
    $workflow = $this->getWorkflow($workflowId);

    if (!$workflow) {
      return NULL;
    }

    $workflowType = $workflow->getTypePlugin();
    return $workflowType;
  }

  /**
   * Get workflow for a workflow.
   *
   * @param string $workflowId
   *   String ID.
   *
   * @return WorkflowInterface|null
   *   Workflow.
   */
  public function getWorkflow(string $workflowId): ?WorkflowInterface {
    /** @var WorkflowInterface $workflow */
    $workflow = Workflow::load($workflowId);
    if (!$workflow) {
      return NULL;
    }
    return $workflow;
  }

  /**
   * Get workflow from workflow for element.
   *
   * @param array $element
   *   Workflows element.
   *
   * @return \Drupal\workflows\WorkflowTypeInterface
   *   Workflow type.
   */
  public function getWorkflowFromElement(array $element): ?WorkflowTypeInterface {
    return isset($element['#workflow']) ? $this->getWorkflowType($element['#workflow']) : NULL;
  }

  /**
   * Get the initial workflow state for the workflow of the element.
   *
   * @param array $element
   *   Workflows element.
   *
   * @return StateInterface
   *   The workflow state.
   */
  public function getInitialStateForElement(array $element): ?StateInterface {
    $workflowType = $this->getWorkflowTypeFromElement($element);

    if (!$workflowType) {
      return NULL;
    }

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

  /**
   * Get all transitions for a current state for a workflow.
   *
   * Optionally also filter by user access.
   *
   * @param string $workflowId
   * @param string $currentStateId
   * @param AccountInterface|null $account
   * @param WebformInterface|null $webform
   *
   * @return array
   *   Array of WorkflowTransitions.
   *
   * @throws Exception
   */
  public function getAvailableTransitionsForWorkflow(string $workflowId, string $currentStateId = NULL, AccountInterface $account = NULL, WebformInterface $webform = NULL, $webform_submission = NULL): array {
    $workflowType = $this->getWorkflowType($workflowId);

    if (!$workflowType) {
      return [];
    }

    /** @var WorkflowInterface $workflow */
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

    // Get available transitions from current state:
    $availableTransitions = $currentState->getTransitions();

    if ($webform && $account) {
      foreach ($availableTransitions as $transition_id => $transition) {
        $access = $this->checkAccessForSubmissionAndTransition($workflow, $account, $webform, $transition, $currentState, $webform_submission);

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
   * @param Transition $transition
   *
   * @return bool
   *   Whether user can transition the workflow.
   *
   * @throws Exception
   */
  public function checkAccessForSubmissionAndTransition(Workflow $workflow, AccountInterface $account, WebformInterface $webform, Transition $transition, $currentState = NULL, $webformSubmission = NULL): bool {
    $pass = FALSE;
    $workflow_elements = $this->getWorkflowElementsForWebform($webform);

    foreach ($workflow_elements as $element) {
      if ($element['#workflow'] != $workflow->id()) {
        continue;
      }

      $transition_access_id = 'access_transition_' . $transition->id();

      $rule_id = 'transition_' . $transition->id();
      if (!webform_workflows_element_check_transition_enabled($element, $transition->id())) {
        return FALSE;
      }

      $pass = $this->checkAccessForWorkflowAccessRules($element, $webform, $account, $transition_access_id, $rule_id);
    }

    // Allow hooks to determine whether access:
    // hook_webform_workflow_element_transition_access_alter
    $context = [
      'workflow' => $workflow,
      'webform' => $webform,
      'state' => $currentState,
      'account' => $account,
      'webform_submission' => $webformSubmission,
      'transition' => $transition,
    ];
    Drupal::moduleHandler()
      ->alter(['webform_workflow_element_transition_access'], $pass, $context);
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
   * @param EntityInterface $webform
   * @param AccountInterface $account
   * @param string $access_id
   * @param string $rule_id
   *
   * @return bool
   *
   * @throws Exception
   */
  public function checkAccessForWorkflowAccessRules(array $element, EntityInterface $webform, AccountInterface $account, string $access_id, string $rule_id): bool {
    /** @var WebformElementManagerInterface $element_manager */
    $element_manager = Drupal::service('plugin.manager.webform.element');

    $element_plugin = $element_manager->getElementInstance($element, $webform);

    $pass = $element_plugin->checkAccessRules($rule_id, $element, $account);

    $groupRoles = isset($element[$access_id . '_group_roles']) && count($element[$access_id . '_group_roles']) > 0;
    $groupPermissions = isset($element[$access_id . '_group_permissions']) && count($element[$access_id . '_group_permissions']) > 0;
    if ($groupRoles || $groupPermissions) {
      if (Drupal::moduleHandler()
        ->moduleExists('webform_group_extended')) {
        $group_access = webform_group_extended_webform_element_access($rule_id, $element, $account);
      }
      elseif (Drupal::moduleHandler()->moduleExists('webform_group')) {
        $group_access = webform_group_webform_element_access($rule_id, $element, $account);
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
    $webformsIDs = $this->getWebformsWithWorkflowElementsIds($workflow_id);
    foreach ($webformsIDs as $id) {
      $webforms[] = Webform::load($id);
    }
    return $webforms;
  }


  /**
   * Load webforms which have workflow elements
   *
   * @param string|null $workflow_id
   *
   * @return array of WebformInterface
   */
  public function getWebformsWithWorkflowElementsIds(string $workflow_id = NULL): array {
    $webforms = [];
    $connection = Database::getConnection();
    $query = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('name', $connection->escapeLike('webform.webform.') . '%', 'LIKE')
      ->condition('data', '%' . $connection->escapeLike('webform_workflows_element') . '%', 'LIKE');

    if ($workflow_id) {
      $query->condition('data', '%' . $connection->escapeLike("'#workflow': " . $workflow_id) . '%', 'LIKE');
    }

    $webformsData = $query->execute();
    foreach ($webformsData as $data) {
      $data = unserialize($data->data);
      $webforms[] = $data['id'];
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
   * @throws Exception
   */
  public function checkUserCanAccessElement(AccountInterface $account, WebformInterface $webform, array $workflow_element, $webform_submission = NULL): bool {
    /** @var WebformElementManagerInterface $element_manager */
    $element_manager = Drupal::service('plugin.manager.webform.element');

    $element_plugin = $element_manager->getElementInstance($workflow_element, $webform);

    $access = NULL;

    // Allow hooks to determine whether access:
    $context = [
      'webform' => $webform,
      'element_plugin' => $element_plugin,
      'account' => $account,
      'workflow_element' => $workflow_element,
      'webform_submission' => $webform_submission,
    ];
    Drupal::moduleHandler()
      ->alter(['webform_workflow_element_access'], $access, $context);

    if (!is_null($access)) {
      return $access;
    }

    // Otherwise use default:
    if (!$element_plugin->checkAccessRules('update', $workflow_element, $account)) {
      return FALSE;
    }
    elseif (count(ElementWebformWorkflowsElement::getAvailableTransitions($workflow_element, $webform_submission)) == 0) {
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
   * @return AccessResultInterface
   */
  public function checkAccessToUpdateBasedOnState(AccountInterface $account, WebformSubmissionInterface $webform_submission, array $element, string $state_id): AccessResultInterface {
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
    }

    /** @var WebformAccessRulesManagerInterface $access_rules_manager */
    $access_rules_manager = Drupal::service('webform.access_rules_manager');

    // The module originally deferred to webform submission's access rules
    // manager as the final check. However, the webforms access rules do not
    // contain any of the workflow state operations, nor the users, roles, or
    // permissions config. It was essentially doing nothing.
    //
    // Conveniently, Webforms has a public method that allows us to check
    // users, roles, and permissions against a list rules in one go.
    $operation = 'update_at_state_' . $state->id();
    $access_rules_result = $access_rules_manager->checkAccessRules($operation, $account, [
        $operation => [
          'users' => $element["#{$state_access_id}_users"] ?? [],
          'roles' => $element["#{$state_access_id}_roles"] ?? [],
          'permissions' => $element["#{$state_access_id}_permissions"] ?? [],
        ],
        // Default is needed because checkAccessRules has a hardcoded check against
        // the "administer" operation that we don't want to be in the business of
        // also hardcoding ourselves.
      ] + $access_rules_manager->getDefaultAccessRules());

    // Based on the wording of the override toggle, it expects to ONLY follow
    // the state's configuration. We cannot use allowedIf because a FALSE
    // becomes neutral(), which is subject to other checks or ultimately be
    // allowed (see \Drupal\Core\Entity\EntityAccessControlHandler::access()).
    // So we explicitly forbid when FALSE, neutral otherwise.
    return AccessResult::forbiddenIf($access_rules_result === FALSE);
  }

  /**
   * Get state for an element with the state ID, or return NULL.
   *
   * @param mixed $element
   *   Workflows element.
   * @param mixed $id
   *   State ID.
   *
   * @return StateInterface
   *   The workflow state.
   */
  public function getStateFromElementAndId($element, $id): ?StateInterface {
    $workflowType = $this->getWorkflowTypeFromElement($element);

    if (!$workflowType) {
      return NULL;
    }

    return $workflowType->hasState($id) ? $workflowType->getState($id) : NULL;
  }

  /**
   * Run transition on element for submission.
   *
   * DOES NOT SAVE SUBMISSION, which means you need to do that for the
   * transition to run.
   *
   * ALWAYS checks access to the transition, so this is safe to run.
   *
   * @param WebformSubmissionInterface $webform_submission
   * @param string $element_id
   * @param array $element
   *
   * @return bool
   * @throws Exception
   */
  public function runTransition(WebformSubmissionInterface &$webform_submission, string $element_id, string $transition_id = NULL, string $log_public = NULL): bool {
    $originalData = $webform_submission->getElementData($element_id);

    // Run the transition:
    $newData = $this->runTransitionOnElementValue($webform_submission, $element_id, $transition_id, $log_public);

    // If data is returned, it's a valid transition to run:
    if ($newData) {
      // Save data to the element:
      $webform_submission->setElementData($element_id, $newData);

      // Trigger event
      // @todo review where this is placed - this is risky if the submission is not actually saved after this function is called.
      $this->triggerTransitionEvent($webform_submission, $element_id, $originalData ?: []);

      Drupal\Core\Cache\Cache::invalidateTags($webform_submission->getCacheTags());

      return TRUE;
    }
    else {
      // If we came by form, reset the submission so the form isn't confused.
      if (!$transition_id) {
        $originalData['transition'] = '';
        $webform_submission->setElementData($element_id, $originalData);
      }
      return FALSE;
    }
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
   * @throws Exception
   */
  public function runTransitionOnElementValue(WebformSubmissionInterface $webform_submission, string $element_id, string $transition_id = NULL, string $log_public = NULL, bool $access_check = TRUE): ?array {
    $elementData = $webform_submission->getElementData($element_id);

    $element = $webform_submission->getWebform()
      ->getElementDecoded($element_id);

    $workflow = Workflow::load($element['#workflow']);
    if (!$workflow) {
      return NULL;
    }

    /** @var WebformWorkflowsElement $workflowType */
    $workflowType = $this->getWorkflowType($element['#workflow']);

    if (!$workflowType) {
      return NULL;
    }

    // Set initial state if no workflow data set:
    if (!$elementData) {
      $initialState = $workflowType->getInitialState();
      if ($initialState) {
        $elementData['workflow_state'] = $initialState->id();
        $elementData['workflow_state_label'] = $initialState->label();
      }
      else {
        return NULL;
      }
    }

    // If not given a transition to run directly, use the one saved by the form:
    if (!$transition_id) {
      $transition_id = isset($elementData['transition']) && $elementData['transition'] ? $elementData['transition'] : NULL;
    }

    // Check if any transitions set to run on edit if no transition set to change:
    if ($elementData && !$transition_id) {
      $transitions = $workflowType->getTransitionsForState($elementData['workflow_state']);
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
            $transition_id = $transition->id();
          }
        }
      }
    }

    // Run transition if set:
    if ($elementData && $transition_id) {
      $transition = $workflowType->getTransition($transition_id);

      if (!$transition) {
        return NULL;
      }

      if ($access_check) {
        // Confirm access to submission and transition:
        $account = Drupal::currentUser();
        $access = $this->checkAccessForSubmissionAndTransition($workflow, $account, $webform_submission->getWebform(), $transition, NULL, $webform_submission);
        if (!$access) {
          return NULL;
        }
      }

      // Set workflow state field value to the value of the new transition:
      $elementData['transition'] = $transition_id;
      $elementData['workflow_state'] = $transition->to()->id();
      $elementData['workflow_state_label'] = $transition->to()->label();

      if ($log_public) {
        $elementData['log_public'] = $log_public;
      }

      // Update who changed and when:
      $elementData['changed_user'] = Drupal::currentUser()->id();
      $elementData['changed_timestamp'] = strtotime('now');
    }

    // Note: workflow_state_previous is set by default on the form.
    return $elementData;
  }

  /**
   * @param WebformSubmissionInterface $submission
   * @param string $element
   */
  public function triggerTransitionEvent(WebformSubmissionInterface $submission, string $element_id, array $originalElementData = NULL) {
    $event = new WebformSubmissionWorkflowTransitionEvent($submission, $element_id, $originalElementData);
    $event_dispatcher = Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, WebformSubmissionWorkflowTransitionEvent::EVENT_NAME);
  }

  /**
   * Log transition if logging is enabled on the webform.
   *
   * @param array $element
   * @param string $element_id
   * @param WebformSubmissionInterface $webform_submission
   * @param array $elementData
   *
   * @throws EntityMalformedException
   */
  public function logTransition(array $element, string $element_id, WebformSubmissionInterface $webform_submission, array $elementData, array $originalElementData = NULL) {
    if (!isset($elementData['transition']) || !$elementData['transition']) {
      return;
    }

    $webform = $webform_submission->getWebform();

    // Log webform submissions to the 'webform_submission' log.
    if (!$webform->hasSubmissionLog()) {
      return;
    }

    /** @var WebformWorkflowsElement $workflowType */
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
      '@state_old' => $originalElementData ? $originalElementData['workflow_state_label'] : '',
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
