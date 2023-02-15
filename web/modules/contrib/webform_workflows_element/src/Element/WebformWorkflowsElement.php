<?php

namespace Drupal\webform_workflows_element\Element;

use Drupal;
use Drupal\user\Entity\User;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'webform_workflows_element'.
 *
 * Copied initially from
 * modules/contrib/webform/modules/webform_group/src/Element/WebformGroupRoles.php.
 *
 * @FormElement("webform_workflows_element")
 */
class WebformWorkflowsElement extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $workflowsManager = Drupal::service('webform_workflows_element.manager');
    $elements = [];

    // Form just for workflow, or prioritising it:
    $workflow_form = Drupal::request()->query->get('transition') || Drupal::request()->query->get('workflow_element');

    $state = NULL;

    if (isset($element['#value']['workflow_state']) && $element['#value']['workflow_state'] != '') {
      $state = $workflowsManager->getStateFromElementAndId($element, $element['#value']['workflow_state']);
    }

    if (!$state) {
      $state = $workflowsManager->getInitialStateForElement($element);
    }

    // Set hidden values to manage the states:
    $elements['workflow_state'] = [
      '#title' => t('Workflow state'),
      '#type' => 'hidden',
    ];

    $elements['workflow_state_previous'] = [
      '#title' => t('Previous workflow state'),
      '#type' => 'hidden',
    ];

    $elements['workflow_state_label'] = [
      '#title' => t('Workflow state label'),
      '#type' => 'hidden',
    ];

    $elements['transition'] = [
      '#title' => t('Transition used most recently'),
      '#type' => 'hidden',
    ];

    $elements['changed_user'] = [
      '#title' => t('User who changed'),
      '#type' => 'hidden',
    ];

    $elements['changed_timestamp'] = [
      '#title' => t('Time last changed'),
      '#type' => 'hidden',
    ];

    $elements['workflow_fieldset'] = [
      '#title' => $element['#title'] ?? t('Workflow'),
      '#type' => 'fieldset',
      '#collapsible' => !$workflow_form,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];

    // Show the user the current state value.
    $html = '';
    if (isset($element['#value'])) {
      $build = [
        '#theme' => 'webform_workflows_element_value',
        '#element' => $element,
        '#values' => $element['#value'],
      ];
      $html = Drupal::service('renderer')->render($build)->__toString();
    }
    $elements['workflow_fieldset']['workflow_state_markup'] = [
      '#markup' => $state ? $html : t('No current workflow state'),
    ];

    // Allow user to select a transition if there are any available:
    $availableTransitions = static::getAvailableTransitions($element);

    // If setting enabled, hide completely
    if (count($availableTransitions) == 0 && isset($element['#hide_if_no_transitions']) && $element['#hide_if_no_transitions']) {
      return [];
    }

    if (count($availableTransitions) > 0) {
      // Can be select or radios:
      $transition_element_type = $element['#transition_element_type'] ?? 'select';

      $options = static::convertTransitionsToOptions($availableTransitions);
      $preset_transition_id = Drupal::request()->query->get('transition');

      if ($preset_transition_id && in_array($preset_transition_id, array_keys($options))) {
        $elements['transition'] = [
          '#title' => t('Transition'),
          '#type' => 'hidden',
          '#value' => $preset_transition_id,
        ];

        $transition = $availableTransitions[$preset_transition_id];
        $elements['workflow_fieldset']['transition_message'] = [
          '#title' => t('Transition'),
          '#type' => 'markup',
          '#markup' => $transition->label(),
          '#description' => t('You have been taken to this page via a link with a preset transition.'),
        ];
      }
      else {
        $required = FALSE;
        if (isset($element['#require_transition_if_available']) && $element['#require_transition_if_available']) {
          $required = TRUE;
        }

        $elements['workflow_fieldset']['transition'] = [
          '#title' => t('Transition'),
          '#type' => $transition_element_type,
          '#description' => t('Some transitions may be hidden if you do not have access, e.g. certain roles.'),
          '#options' => $options,
          '#empty_option' => t('- select transition -'),
          '#default_value' => '',
          '#required' => $required,
          '#attributes' => [
            'class' => ['workflow-transition'],
          ],
        ];
      }

      if ($element['#log_public_setting'] != 'Disabled') {
        $elements['workflow_fieldset']['log_public'] = [
          '#title' => t('Log message for submitter'),
          '#type' => 'textarea',
          '#rows' => 2,
          '#required' => $element['#log_public_setting'] === 'Required',
        ];
      }

      if ($element['#log_admin_setting'] != 'Disabled') {
        $elements['workflow_fieldset']['log_admin'] = [
          '#title' => t('Log message - admin only'),
          '#type' => 'textarea',
          '#rows' => 2,
          '#required' => $element['#log_admin_setting'] === 'Required',
        ];
      }
    }
    else {
      // @todo different message if it's access preventing transitions, versus just not having any available transitions
      $elements['workflow_fieldset']['transitions_message'] = [
        '#title' => t('Transitions'),
        '#type' => 'markup',
        '#markup' => t("No transitions are available to you. You may not have the required access, or the workflow may have reached the end of a process."),
      ];
      $elements['transition'] = [
        '#title' => t('Transition'),
        '#type' => 'hidden',
      ];
    }

    return $elements;
  }

  /**
   * Get the available transitions for an element of a submission.
   *
   * @param array $element
   *   Workflow element array.
   * @param bool $checkAccess
   *   Whether to check current user access.
   *
   * @return array
   *   Of available transitions.
   */
  public static function getAvailableTransitions(array $element, bool $checkAccess = TRUE): array {
    if (!isset($element['#workflow'])) {
      return [];
    }
    $webform = isset($element['#webform']) ? Webform::load($element['#webform']) : NULL;
    $account = User::load(Drupal::currentUser()->id());
    $workflowsManager = Drupal::service('webform_workflows_element.manager');

    // If no state is set, assume the initial state:
    $initial_state = $workflowsManager->getInitialStateForElement($element) ? $workflowsManager->getInitialStateForElement($element)
      ->id() : '';
    $state_is_set = isset($element['#value']['workflow_state']) && $element['#value']['workflow_state'] && $element['#value']['workflow_state'] != '';
    if ($state_is_set) {
      $current_state = $element['#value']['workflow_state'];
    }
    else {
      $current_state = $initial_state;
    }

    $workflow_id = $element['#workflow'];
    return $workflowsManager->getAvailableTransitionsForWorkflow($workflow_id, $current_state, $checkAccess ? $account : NULL, $webform);
  }

  /**
   * Convert transitions into options for a select.
   *
   * @param array $transitions
   *   Transitions to convert.
   *
   * @return array
   *   options keyed by id
   */
  public static function convertTransitionsToOptions(array $transitions): array {
    $options = [];
    foreach ($transitions as $transition) {
      $options[$transition->id()] = $transition->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'webform_workflows_element'];
  }

}
