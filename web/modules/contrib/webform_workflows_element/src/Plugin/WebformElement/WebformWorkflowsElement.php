<?php

namespace Drupal\webform_workflows_element\Plugin\WebformElement;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Provides a 'WebformWorkflowsElement' element.
 *
 * @WebformElement(
 *   id = "webform_workflows_element",
 *   label = @Translation("Webform workflows element"),
 *   description = @Translation("Provides a webform workflows element."),
 *   category = @Translation("Workflow"),
 *   multiple = FALSE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 *   multiline = TRUE,
 *   default_key = "workflow",
 *   dependencies = {
 *     "workflows",
 *   }
 * )
 *
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformWorkflowsElement extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $webform = $form_state->getFormObject()->getWebform();

    $color_options = ['' => 'None'] + webform_workflows_element_get_color_options();

    unset($form['composite']['element']);
    unset($form['composite']['flexbox']);

    $form['composite'] = [
        'workflow' => [
          '#type' => 'select',
          '#title' => $this->t('Workflow'),
          '#description' => $this->t('Please select a workflow. <a href=":href-summary" target="_blank">View a summary of workflows here</a>, or <a href=":href">manage workflows here</a>.', [
            ':href' => Url::fromRoute('entity.workflow.collection')->toString(),
            ':href-summary' => Url::fromRoute('webform_workflows_element.workflows_summary', ['webform' => $webform->id()])
              ->toString(),
          ]),
          '#options' => $this->getWorkflowOptions(),
          '#required' => TRUE,
        ],
      ] + $form['composite'];

    $form['composite'] = $form['composite'] +
      [
        'show_workflow_form_on_view' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow changing the workflow state from the "view submission" page'),
          '#description' => $this->t('Provides a shortcut to updating the workflow without needing to go to the edit submission form.'),
        ],
      ];

    $form['composite']['transition_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Transition user interface'),
      '#open' => TRUE,
    ];
    $form['composite']['transition_options'][] =
      [
        'require_transition_if_available' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Require user select a transition if one available'),
        ],
      ];
    $form['composite']['transition_options'][] =
      [
        'hide_if_no_transitions' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide element if no transitions available for the user'),
        ],
      ];

    $form['composite']['transition_options'][] =
      [
        'log_public_setting' => [
          '#type' => 'select',
          '#title' => $this->t('Log message - shown to submitter'),
          '#description' => $this->t('When transitioning a workflow state, users can enter log messages. The log message for submitter collects a log message the submitting user can see on their submission. You can require it, have it be optional, or hide it all together.'),
          '#options' => [
            'Optional' => 'Optional',
            'Required' => 'Required',
            'Disabled' => 'Disabled',
          ],
        ],
      ];

    $form['composite']['transition_options'][] =
      [
        'log_admin_setting' => [
          '#type' => 'select',
          '#title' => $this->t('Log message - admin only'),
          '#description' => $this->t('When transitioning a workflow state, users can enter log messages. The log message for submitter log message collects a log message only those with webform editing access. You can require it, have it be optional, or hide it all together.'),
          '#options' => [
            'Optional' => 'Optional',
            'Required' => 'Required',
            'Disabled' => 'Disabled',
          ],
        ],
      ];

    $form['composite']['transition_options'][] =
      [
        'transition_element_type' => [
          '#type' => 'select',
          '#title' => $this->t('Element type for transition'),
          '#options' => [
            'select' => t('Select (dropdown)'),
            'radios' => t('Radio buttons'),
          ],
        ],
      ];

    // Some options won't show until element is saved with a workflow for the first time:
    $workflow_id = $form_state->get('element_properties')['workflow'];
    $workflow = Workflow::load($workflow_id);
    if (!$workflow) {
      $form['composite'] = $form['composite'] + [
          'save_notice' => [
            '#type' => 'webform_message',
            '#message_message' => $this->t('Save this element with a workflow to see more options.'),
            '#message_type' => 'info',
          ],
        ];
    }

    // Logs:
    $form['composite']['log_history'] = [
      '#type' => 'details',
      '#title' => $this->t('Log history'),
      '#open' => TRUE,
    ];
    if (Drupal::moduleHandler()->moduleExists('webform_submission_log')) {

      $form['composite']['log_history'][] =
        [
          'show_log_view' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Show workflow log history on "view submission" page'),
            '#description' => $this->t('Regardless of this setting, the log is only ever shown to users with "update" access to the element.'),
          ],
        ];

      $form['composite']['log_history'][] =
        [
          'show_log_edit' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Show workflow log history on "edit submission" form'),
            '#description' => $this->t('Regardless of this setting, the log is only ever shown to users with "update" access to the element.'),
          ],
        ];
    }
    else {

      $form['composite']['log_history'][] =
        [
          'log_notice' => [
            '#type' => 'webform_message',
            '#message_message' => $this->t('It is recommended the "Webform submission log" module be enabled to track changes in workflow for your submissions.'),
            '#message_type' => 'warning',
          ],
        ];
    }

    $states = [];

    // Add transition and state options:
    if ($form_state->get('element_properties')['workflow'] != '') {
      $workflow = Workflow::load($form_state->get('element_properties')['workflow']);
      $transitions = $workflow->getTypePlugin()->getTransitions();
      $states = $workflow->getTypePlugin()->getStates();

      // Conditional setup - allows user to disable certain states with conditional logic
      $conditional_states = &$form['conditional_logic']['states']['#state_options'];
      $optgroup = (string) $this->t('Workflow transitions');
      $conditional_states[$optgroup] = [];

      $enabledStates = ['access_view', 'access_update', 'access_create'];
      foreach ($enabledStates as $state) {
        if ($state == 'access_create') {
          $title = t('Enable access to workflow element when creating a new webform submission (subject to other access requirements below)');
        }
        else {
          $title = t('Enable access to workflow element (subject to other access requirements below)');
        }
        $form['access'][$state][$state . '_workflow_enabled'] = [
          '#type' => 'checkbox',
          '#title' => $title,
          '#description' => t('Uncheck this box to prevent any element access for this action.'),
          '#weight' => -100,
        ];
      }

      foreach ($transitions as $transition) {
        $id = 'transition_' . $transition->id();

        // Add transition settings box:
        $form['composite'][$id] = [
          '#type' => 'details',
          '#title' => t('Transition: @label', ['@label' => $transition->label()]),
        ];

        // Settings:
        $form['composite'][$id][$id . '_run_on_edit'] = [
          '#type' => 'select',
          '#title' => $this->t('Run "@transition" transition automatically when submission is edited', [
            '@transition' => $transition->label(),
          ]),
          '#options' => [
            '' => 'Do not run transition automatically',
            'owner' => 'When edited by submission owner',
            'anyone' => 'When edited by anyone',
          ],
          '#description' => $this->t('The transition must be available based on the submission state or nothing will happen. If necessary, create a transition selecting every single state as "from". Note that if a state allows multiple transitions that are set to run on editing, only the last one will run.'),
          '#required' => FALSE,
        ];

        // @todo show this on form when conditional disables the transition:
        //
        // $form['composite'][$id][$id . '_disabled_message'] = [
        //   '#type' => 'textarea',
        //   '#rows' => 2,
        //   '#title' => $this->t('Message if disabled by a conditional'),
        //   '#description' => $this->t('If you disable transitions on the Conditional tab, this will be displayed to explain to the user why they might not be able to select a transition.'),
        //   '#required' => FALSE,
        // ];

        // Conditional - add a 'disable' state for each transition
        // @todo get actual elementId for element Id, not workflow Id, then use to allow for multiple workflow elements . $elementId . '-'
        //        $elementId = $form_state->get('element_properties')['workflow'];
        $conditional_states[$optgroup]['disable_transition-' . $transition->id()] = 'Disable "' . $transition->label() . '" transition';

        // Access settings interface - use update as a base.
        // See also webform_group_form_webform_ui_element_form_alter
        $transition_access_id = 'access_transition_' . $transition->id();
        $form['access'][$transition_access_id] = $form['access']['access_update']; // copy from update

        $form['access'][$transition_access_id]['#title'] = $this->t('Workflow transition "@label"', ['@label' => $transition->label()]);
        $form['access'][$transition_access_id]['#description'] = $this->t('Select roles and users that should be able to use transition "@label" to change submission status to @state', [
          '@label' => $transition->label(),
          '@state' => $transition->to()->label(),
        ]);

        $permissions_properties = [
          'workflow_enabled',
          'roles',
          'users',
          'permissions',
          'group_roles',
          'group_membership_record',
        ];
        foreach ($permissions_properties as $property) {
          if (isset($form['access']['access_update']['access_update_' . $property])) {
            $form['access'][$transition_access_id][$transition_access_id . '_' . $property] = $form['access']['access_update']['access_update_' . $property];
            unset($form['access'][$transition_access_id]['access_update_' . $property]);
          }

          if ($property == 'workflow_enabled') {
            $default_properties[$transition_access_id . '_' . $property] = TRUE;
            $form['access'][$transition_access_id][$transition_access_id . '_workflow_enabled']['#title'] = t('Allow this transition (subject to the access restrictions below, if any)');
          }
          else {
            $default_properties[$transition_access_id . '_' . $property] = [];
          }
        }
      }
    }

    foreach ($states as $state) {
      $id = 'state_' . $state->id();

      // Access settings interface - use update as a base.
      // See also webform_group_form_webform_ui_element_form_alter
      $state_access_id = 'access_update_at_state_' . $state->id();

      $form['access'][$state_access_id] = $form['access']['access_update']; // copy from update
      $form['access'][$state_access_id]['#title'] = $this->t('Override default submission update access at submission workflow state: "@label"', ['@label' => $state->label()]);
      $form['access'][$state_access_id]['#description'] = $this->t('Select roles and users that should be able to edit the submission when it is at @label. This overrides the submission\'s default update access settings.', [
        '@label' => $state->label(),
      ]);

      $form['access'][$state_access_id][$state_access_id . '_override'] = [
        '#type' => 'checkbox',
        '#title' => t('Override default submission editing access and allow editing the submission only according to the below access settings when submission is at workflow state @label', ['@label' => $state->label()]),
        '#description' => t('Note this refers to the whole submission, not just the workflow element. Note users with "administer webform and submissions" access always have access to edit the submission.'),
        '#weight' => -100,
      ];

      $permissions_properties = [
        'workflow_enabled',
        'roles',
        'users',
        'permissions',
        'group_roles',
        'group_membership_record',
      ];
      foreach ($permissions_properties as $property) {
        if (isset($form['access']['access_update']['access_update_' . $property])) {
          $form['access'][$state_access_id][$state_access_id . '_' . $property] = $form['access']['access_update']['access_update_' . $property];
          unset($form['access'][$state_access_id]['access_update_' . $property]);
        }

        if ($property == 'workflow_enabled') {
          $default_properties[$state_access_id . '_' . $property] = TRUE;
          $form['access'][$state_access_id][$state_access_id . '_workflow_enabled']['#title'] = t('Allow editing of the submission at this state - CAUTION disabling may prevent changing transition at all');
          $form['access'][$state_access_id][$state_access_id . '_workflow_enabled']['#weight'] = -99;
        }
        else {
          $default_properties[$state_access_id . '_' . $property] = [];
        }
      }

      // Add state settings box:
      $form['composite'][$id] = [
        '#type' => 'details',
        '#title' => t('State: @label', ['@label' => $state->label()]),
      ];

      $form['composite'][$id][$id . '_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Color'),
        '#description' => $this->t('For results pages.'),
        '#options' => $color_options,
        '#required' => FALSE,
      ];
    }

    return $form;
  }

  /**
   * Get the webform workflows available.
   */
  public static function getWorkflowOptions(): array {
    $options = [];
    $workflows = Workflow::loadMultipleByType('webform_workflows_element');
    foreach ($workflows as $workflow) {
      $id = $workflow->id();
      $label = $workflow->label();
      $options[$id] = t('@label', [
        '@label' => $label,
      ]);
    }
    ksort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format == 'value') {
      $build = [
        '#theme' => 'webform_workflows_element_value',
        '#element' => $element,
        '#values' => $this->getValue($element, $webform_submission, $options),
      ];
      $html = Drupal::service('renderer')->render($build)->__toString();

      return [
        $html,
      ];
    }
    $composite_elements = $this->getInitializedCompositeElement($element);
    $composite_elements += $composite_elements['workflow_fieldset'];

    if (in_array($format, $composite_elements)) {
      return $this->formatCompositeHtml($element, $webform_submission, ['composite_key' => $format] + $options);
    }

    return parent::formatHtmlItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getInitializedCompositeElement(array $element, $composite_key = NULL) {
    $composite_elements = $element['#webform_composite_elements'];

    // Add workflow fieldset values as well:
    if (isset($composite_elements['workflow_fieldset'])) {
      $composite_elements += $composite_elements['workflow_fieldset'];
    }

    if (isset($composite_key)) {
      return $composite_elements[$composite_key] ?? NULL;
    }
    else {
      return $composite_elements;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    $properties = parent::defineDefaultProperties();

    $properties['multiple'] = FALSE;

    // Core settings:
    $properties['workflow'] = '';
    $properties['log_public_setting'] = '';
    $properties['log_admin_setting'] = '';
    $properties['transition_element_type'] = 'select';
    $properties['require_transition_if_available'] = FALSE;
    $properties['hide_if_no_transitions'] = FALSE;
    $properties['show_workflow_form_on_view'] = TRUE;
    $properties['show_log_view'] = TRUE;
    $properties['show_log_edit'] = TRUE;
    $properties['allow_restricting_editing_by_state'] = FALSE;

    // Default to not showing on create form:
    $properties['access_view_workflow_enabled'] = TRUE;
    $properties['access_create_roles'] = [];
    $properties['access_create_workflow_enabled'] = FALSE;
    $properties['access_update_roles'] = ['authenticated'];
    $properties['access_update_workflow_enabled'] = TRUE;

    // Set up properties for all possible transitions and states
    // @todo find a way to only set up the properties for the selected workflow...
    $transitions = static::getAllWorkflowTransitions();
    foreach ($transitions as $transition) {
      $properties['transition_' . $transition->id() . '_color'] = '';
      $properties['transition_' . $transition->id() . '_disabled_message'] = '';
      $properties['transition_' . $transition->id() . '_run_on_edit'] = '';
      $properties['access_transition_' . $transition->id() . '_workflow_enabled'] = TRUE;
      $properties['access_transition_' . $transition->id() . '_roles'] = [];
      $properties['access_transition_' . $transition->id() . '_users'] = [];
      $properties['access_transition_' . $transition->id() . '_permissions'] = [];
      $properties['access_transition_' . $transition->id() . '_group_roles'] = [];
      $properties['access_transition_' . $transition->id() . '_group_permissions'] = [];
    }

    $states = static::getAllWorkflowStates();
    foreach ($states as $state) {
      $properties['state_' . $state->id() . '_color'] = '';
      $properties['state_' . $state->id() . '_allow_resubmission_transition'] = NULL;

      $properties['access_update_at_state_' . $state->id() . '_override'] = FALSE;
      $properties['access_update_at_state_' . $state->id() . '_workflow_enabled'] = TRUE;
      $properties['access_update_at_state_' . $state->id() . '_roles'] = [];
      $properties['access_update_at_state_' . $state->id() . '_users'] = [];
      $properties['access_update_at_state_' . $state->id() . '_permissions'] = [];
      $properties['access_update_at_state_' . $state->id() . '_group_roles'] = [];
      $properties['access_update_at_state_' . $state->id() . '_group_permissions'] = [];
    }

    return $properties;
  }

  /**
   * Get all transitions available for all available workflows.
   */
  public static function getAllWorkflowTransitions(): array {
    $options = [];
    $workflows = Workflow::loadMultipleByType('webform_workflows_element');
    foreach ($workflows as $workflow) {
      $workflowsManager = Drupal::service('webform_workflows_element.manager');
      $workflowType = $workflowsManager->getWorkflowType($workflow->id());
      $options = array_merge($options, $workflowType->getTransitions());
    }
    return $options;
  }

  /**
   * Get all states available for all available workflows.
   */
  public static function getAllWorkflowStates(): array {
    $workflowsManager = Drupal::service('webform_workflows_element.manager');

    $options = [];
    $workflows = Workflow::loadMultipleByType('webform_workflows_element');
    foreach ($workflows as $workflow) {
      $workflowType = $workflowsManager->getWorkflowType($workflow->id());
      $options = array_merge($options, $workflowType->getStates());
    }
    return $options;
  }

}
