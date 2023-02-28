<?php

namespace Drupal\webform_workflows_element\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupRole;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\webform\WebformInterface;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

class WorkflowsSummaryController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Return a set of details containing summaries of the transitions for each
   * webform workflow.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *
   * @return array
   *   Render array
   */
  function renderSummary(WebformInterface $webform = NULL): array {
    $build = [];

    $build['intro'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#markup' => $this->t("This shows all available webform workflows. Note that you can always disable transitions via the element's access settings, so you can use a workflow that has more transitions/states than you actually need."),
      '#suffix' => '</p>',
    ];

    $buildWorkflows = [];
    $workflows = Workflow::loadMultipleByType('webform_workflows_element');
    foreach ($workflows as $workflow) {
      $buildWorkflows[$workflow->id()] = [
        '#type' => 'details',
        '#title' => $workflow->label(),
      ];

      $buildWorkflows[$workflow->id()][] = $this->renderWorkflowSummaryTable($workflow);
    }

    usort($buildWorkflows, function ($a, $b) {
      return strcmp($a['#title'], $b['#title']);
    });

    return array_merge($build, $buildWorkflows);
  }

  /**
   * Render a summary table for a workflow.
   *
   * @param WorkflowInterface $workflow
   * @param \Drupal\webform\WebformInterface|null $webform
   * @param string|null $element_id
   *
   * @return array
   *   Render array of table.
   */
  public function renderWorkflowSummaryTable(WorkflowInterface $workflow, WebformInterface $webform = NULL, string $element_id = NULL): array {
    $rows_enabled = [];
    $rows_disabled = [];

    $transitions = $workflow->getTypePlugin()->getTransitions();

    $element = NULL;
    if ($element_id && $webform) {
      $element = $webform->getElementDecoded($element_id);
    }

    foreach ($transitions as $transition) {
      $enabled_key = '#access_transition_' . $transition->id() . '_workflow_enabled';
      $disabled = $element && isset($element[$enabled_key]) && !$element[$enabled_key];

      $fromStates = $transition->from();
      $fromStateNames = [];
      foreach ($fromStates as $state) {
        $fromStateNames[] = $this->renderState($state, $element);
      }

      $from = [
        '#theme' => 'item_list',
        '#items' => $fromStateNames,
        '#context' => ['list_style' => 'comma-list'],
      ];
      $from = Drupal::service('renderer')->render($from);

      $to = $transition->to();
      $toMarkup = $this->renderState($to, $element);
      $toRendered = Drupal::service('renderer')->render($toMarkup);

      $row = [
        'Transition' => $transition->label(),
        'From states' => $from,
        'To state' => $toRendered,
      ];

      if ($webform) {
        // Access:
        $access = $this->getTransitionAccessSummary($transition, $webform, $element_id);
        $element_access_list = [
          '#theme' => 'item_list',
          '#prefix' => $access['transition_access'] ? t('<p><b>Element access</b></p>') : NULL,
          '#list_type' => 'ul',
          '#items' => $access['element_access'],
        ];
        $transition_access_list = $access['transition_access'] ? [
          '#theme' => 'item_list',
          '#prefix' => t('<p><b>Transition access</b></p>'),
          '#list_type' => 'ul',
          '#items' => $access['transition_access'],
        ] : [];
        $access_lists = [
          $element_access_list,
          $transition_access_list,
        ];
        $row['Access'] = Drupal::service('renderer')->render($access_lists);

        // Emails:
        $emails_list = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#items' => $this->getTransitionEmailSummary($transition, $webform, $element_id),
        ];
        $row['E-mails'] = Drupal::service('renderer')->render($emails_list);
      }

      if ($disabled) {
        $rows_disabled[] = $row;
      }
      else {
        $rows_enabled[] = $row;
      }
    }

    $editElementLink = '';
    if ($webform && $element_id) {
      $editElementLink = Url::fromRoute(
          'entity.webform_ui.element.edit_form',
          [
            'webform' => $webform->id(),
            'key' => $element_id,
          ],
        )->toString() . '#webform-tab--access';
    }

    $enabled = [
      '#type' => 'table',
      '#header' => count($rows_enabled) > 0 ? array_keys($rows_enabled[0]) : [],
      '#rows' => $rows_enabled,
      '#empty' => t(
        'No enabled transitions for this workflow for this form. <a href=":href">Manage transition availability on the "Access" tab of the workflow element</a>.',
        [
          ':href' => $editElementLink,
        ]
      ),
      '#attached' => [
        'library' => [
          'webform_workflows_element/default_colors',
        ],
      ],
    ];

    // Split into enabled and disabled.
    if ($webform) {
      $enabled['#prefix'] = '<h3>' . $this->t('Enabled transitions') . '</h3>';
      $enabled['#title'] = t('Enabled transitions');

      $disabled = [
        '#prefix' => '<h3>' . $this->t('Disabled transitions') . '</h3>',
        '#title' => t('Disabled transitions'),
        '#type' => 'table',
        '#header' => count($rows_disabled) > 0 ? array_keys($rows_disabled[0]) : [],
        '#rows' => $rows_disabled,
        '#empty' => t(
          'No disabled transitions for this workflow for this form. <a href=":href">Manage transition availability on the "Access" tab of the workflow element</a>.',
          [
            ':href' => $editElementLink,
          ]
        ),
        '#attached' => [
          'library' => [
            'webform_workflows_element/default_colors',
          ],
        ],
      ];

      return [
        $enabled,
        $disabled,
      ];
    }
    else {
      return [$enabled];
    }
  }

  /**
   * Render a state with colours etc.
   *
   * @param Drupal\workflows\StateInterface $state
   * @param array|NULL $element
   * @param string $suffix
   *
   * @return array|string[]
   */
  public function renderState(StateInterface $state, array $element = NULL, string $suffix = ''): array {
    $color_options = webform_workflows_element_get_color_options_values();

    if ($element && isset($element['#state_' . $state->id() . '_color']) && $color_name = $element['#state_' . $state->id() . '_color']) {
      $color = $color_options[$color_name];
      return [
        '#type' => 'markup',
        '#markup' => Markup::create('<span class="webform-workflow-state-label with-color ' . $color . '">' . $state->label() . '</span> '),
      ];
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => $state->label() . $suffix,
      ];
    }
  }

  /**
   * Get summary of access to the transition.
   *
   * Based on element update access AND transition custom access.
   *
   * @param mixed $transition
   * @param mixed $webform
   * @param mixed $element_id
   *
   * @return array
   *   Array keyed by element_access and transition_access.
   */
  public function getTransitionAccessSummary($transition, $webform, $element_id): array {
    $element_access = [];
    $transition_access = [];
    $element = $webform->getElementDecoded($element_id);

    // Process update access rules:
    foreach ($element as $key => $value) {
      // Overall element update access:
      $access_key = '#access_update_';
      if (strstr($key, '#access_update_')) {
        $access_type = str_replace($access_key, '', $key);
        $element_access = $element_access + $this->getTransitionAccessSummaryConvertToText($access_type, $value);
      }

      // Get transition-specific access:
      $access_key = '#access_transition_' . $transition->id() . '_';
      if (!strstr($key, $access_key)) {
        continue;
      }

      $access_type = str_replace($access_key, '', $key);
      // Sometimes transition is just completely disabled:
      if ($access_type == 'workflow_enabled' && !$value) {
        return [
          'element_access' => [t('Transition is disabled for this form.')],
          'transition_access' => [],
        ];
      }
      else {
        $transition_access = $transition_access + $this->getTransitionAccessSummaryConvertToText($access_type, $value);
      }
    }

    $element_access = array_unique($element_access);
    $transition_access = array_unique($transition_access);
    $transition_access = array_diff_key($transition_access, $element_access);

    return [
      'element_access' => $element_access,
      'transition_access' => $transition_access,
    ];
  }

  /**
   * Get user-friendly explanation for access type.
   *
   * @param string $access_type
   * @param array|string $value
   *
   * @return array
   *   Array of markups.
   */
  public function getTransitionAccessSummaryConvertToText(string $access_type, $value): array {
    $access = [];
    switch ($access_type) {
      case 'roles':
        foreach ($value as $role) {
          $access[$access_type . $role] = t('User role "@label"', [
            '@label' => Role::load($role)->label(),
          ]);
        }
        break;

      case 'users':
        foreach ($value as $uid) {
          $access[$access_type . $uid] = t('User "@label"', [
            '@label' => User::load($uid)->getDisplayName(),
          ]);
        }
        break;

      case 'permissions':
        foreach ($value as $permission) {
          $access[$access_type . $permission] = t('User permission "@label"', [
            '@label' => $permission,
          ]);
        }
        break;

      case 'group_roles':
        foreach ($value as $role) {
          $access[$access_type . $role] = t('Group role "@label"', [
            '@label' => GroupRole::load($role)->label(),
          ]);
        }
        break;

      case 'group_permissions':
        foreach ($value as $permission) {
          $access[$access_type . $permission] = t('Group permission "@label"', [
            '@label' => $permission,
          ]);
        }
        break;
    }

    return $access;
  }

  /**
   * Get array of links to the e-mail handlers for a transition.
   *
   * @param mixed $transition
   * @param mixed $webform
   * @param mixed $element_id
   *
   * @return array
   *   Array of markup links.
   */
  public function getTransitionEmailSummary($transition, $webform, $element_id): array {
    $emails = [];
    $handlers = $webform->getHandlers('workflows_transition_email');
    foreach ($handlers as $handler) {
      $states = $handler->getConfiguration()['settings']['states'];
      $key = $element_id . ':' . $transition->id();
      if (!in_array($key, $states)) {
        continue;
      }

      $uri = Url::fromRoute('entity.webform.handler.edit_form', [
        'webform' => $webform->id(),
        'webform_handler' => $handler->getHandlerId(),
      ])->setAbsolute()->toString();

      $email = [
        '#type' => 'markup',
        '#markup' => t('<a href="@url">"@label"</a> to <i>@to</i>', [
          '@label' => $handler->label(),
          '@to' => $handler->getConfiguration()['settings']['to_mail'],
          '@url' => $uri,
        ]),
      ];

      $emails[] = $email;
    }
    return $emails;
  }

  /**
   * @param \Drupal\webform\WebformInterface|NULL $webform
   *
   * @return array
   */
  function renderSummaryForWebform(WebformInterface $webform = NULL): array {
    $workflowsManager = Drupal::service('webform_workflows_element.manager');
    $workflow_elements = $workflowsManager->getWorkflowElementsForWebform($webform);
    if (!count($workflow_elements)) {
      $url = Url::fromRoute('entity.webform_ui.element.add_form', [
        'webform' => $webform->id(),
        'type' => 'webform_workflows_element',
      ]);
      return [
        '#type' => 'markup',
        '#markup' => t(
          'There are no "webform workflow element" elements in the form. <a href="@link">Add one now</a>.',
          [
            '@link' => $url->toString(),
          ]
        ),
      ];
    }

    $render = [];
    foreach ($workflow_elements as $element_id => $element) {
      $render[$element['#title']] = $this->renderSummaryForWebformElement($element, $element_id, $webform);
    }

    if (count($workflow_elements) > 1) {
      $grouped_render = [];
      foreach ($render as $title => $rendered_workflow) {
        $grouped_render[] = [
          '#type' => 'fieldset',
          '#title' => $title,
          'rendered' => $rendered_workflow,
        ];
      }
      return $grouped_render;
    }

    return $render;
  }

  /**
   * @param array $element
   * @param string $element_id
   * @param \Drupal\webform\WebformInterface|NULL $webform
   *
   * @return array[]
   */
  function renderSummaryForWebformElement(array $element, string $element_id, WebformInterface $webform = NULL): array {
    $workflow = Workflow::load($element['#workflow']);
    $workflowType = $workflow->getTypePlugin();
    $initialState = $workflowType->getInitialState();

    $render = [
      'overall_summary' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Overall summary'),
        'workflow' => [
          '#prefix' => t('<b>Workflow: </b>'),
          '#type' => 'link',
          '#url' => Url::fromRoute('entity.workflow.edit_form', ['workflow' => $workflow->id()]),
          '#title' => t('@label', [
            '@label' => $workflow->label(),
          ]),
        ],
        'initial_state' => [
          '#type' => 'markup',
          '#prefix' => t('<div><b>Initial state for new submissions: </b>'),
          '#markup' => '<span class="webform-workflow-state-label with-color ' . webform_workflows_element_get_color_class_for_state_from_element($element, $initialState->id()) . '">' . $initialState->label() . '</span>',
          '#suffix' => t('</div>'),
        ],
        'table' => $this->renderWorkflowSummaryTable($workflow, $webform, $element_id),
        'table_help' => [
          '#type' => 'markup',
          '#markup' => t('<p>"From" indicates what states can start the transition.</p><p>"To" indicates what the state will be after the transition.</p><p>"Access" outlines who can run the transition, e.g. they have to meet at least one of the user roles. If access is split into element and transition access, the user must meet both element access and transition access.</p>'),
        ],
      ],
    ];

    if (Drupal::moduleHandler()->moduleExists('workflows_diagram')) {
      $render[] = $this->renderSummaryAsDiagram($workflow, $webform, $element_id);
    }

    $render[] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Understanding workflows'),
      'help' => [
        '#markup' => t(
          '<p>Workflows are made up of "states" which a submission can be at - e.g. "submitted" or "approved".</p>'
          . '<p>A submission moves from one state to another by a "transition". e.g. the "approve" transition could move a submission from the "submitted" state to the "approved" state.</p>'
          . '<p>A transition could be set from multiple states to one, e.g. "submitted" and "queried" could both be set to go to the "rejected" state using a "reject" transition.</p>'
          . '<p>Not all states need to transition to all other states, so you could e.g. have a "rejected" state that can not transition to "approved".<p>'
          . '<p>More help on using webform workflows can be found <a target="_blank" href="@doc_link">on our documentation page</a>.</p>',
          [
            '@doc_link' => 'https://www.drupal.org/docs/contributed-modules/webform-workflows-element',
          ],
        ),
      ],
    ];

    return $render;
  }

  /**
   * @param \Drupal\workflows\WorkflowInterface $workflow
   * @param \Drupal\webform\WebformInterface $webform
   * @param string $element_id
   *
   * @return array
   */
  public function renderSummaryAsDiagram(WorkflowInterface $workflow, WebformInterface $webform, string $element_id): array {
    $workflowType = $workflow->getTypePlugin();

    $element = $webform->getElementDecoded($element_id);

    $states_in_use = [
      $workflowType->getInitialState()
        ->id() => $workflowType->getInitialState(),
    ];

    // Check if anything has been disabled as part of the workflow:
    $disabled = [
      'transitions' => [],
      'states' => [],
    ];

    foreach ($workflowType->getTransitions() as $transition) {
      $enabled_key = '#access_transition_' . $transition->id() . '_workflow_enabled';
      if (isset($element[$enabled_key]) && !$element[$enabled_key]) {
        $disabled['transitions'][] = $transition->id();
      }
      else {
        $states_in_use = $states_in_use + [
            $transition->to()
              ->id() => $transition->to(),
          ];
      }
    }

    // Loop through states:
    $classes = ['states' => []];

    foreach ($workflowType->getStates() as $state) {
      if (!in_array($state->id(), array_keys($states_in_use))) {
        $disabled['states'][] = $state->id();
        continue;
      }

      $color_class = webform_workflows_element_get_color_class_for_state_from_element($element, $state->id());
      if ($color_class) {
        $classes['states'][$state->id()] = [
          'webform-workflow-state-label',
          'with-color',
          $color_class,
        ];
      }
    }

    return [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Diagram of workflow'),
      'diagram' => [
        '#theme' => 'workflows_diagram',
        '#workflow' => $workflow,
        '#classes' => $classes,
        '#disabled' => $disabled,
        '#attached' => [
          'library' => [
            'webform_workflows_element/default_colors',
          ],
        ],
      ],
    ];
  }

}
