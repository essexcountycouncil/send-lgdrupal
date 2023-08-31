<?php

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Drupal\webform_workflows_element\WebformSubmissionWorkflowListBuilder;

/**
 * Add list builder class to make filterable by state.
 *
 * @param array $entity_types
 *   Array of entity types, by reference.
 */
function webform_workflows_element_entity_type_alter(array &$entity_types) {
  // If enabled, Webform Better Results will automatically pick up the hooks we use.
  // If not enabled, do it manually from within this module.
  if (!Drupal::moduleHandler()->moduleExists('webform_better_results')) {
    $entity_types['webform_submission']->setHandlerClass('list_builder', WebformSubmissionWorkflowListBuilder::class);
  }
}

/**
 * Add filter to results list builder per element
 *
 * @param mixed $form
 * @param FormStateInterface $form_state
 * @param mixed $form_id
 */
function webform_workflows_element_form_webform_submission_filter_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // If enabled, Webform Better Results will automatically pick up the hooks we use.
  // If not enabled, do it manually from within this module.
  if (Drupal::moduleHandler()->moduleExists('webform_better_results')) {
    return;
  }

  $webform = NULL;

  $webform_id = Drupal::routeMatch()
    ->getRawParameter('webform'); // There must be a better way to get the webform...
  if ($webform_id) {
    $webform = Webform::load($webform_id);
  }
  else {
    if ($nid = Drupal::routeMatch()->getRawParameter('node')) {
      if ($node = Node::load($nid)) {
        /** @var WebformEntityReferenceManagerInterface $entity_reference_manager */
        $entity_reference_manager = Drupal::service('webform.entity_reference_manager');
        // Check that the node has a webform field that has been populated.
        $webform = $entity_reference_manager->getWebform($node);
      }
    }
  }

  if (!$webform) {
    return;
  }

  $form['#submit'][] = 'webform_workflows_element_webform_submission_filter_form_submit';

  $data = [
    'form' => $form,
    'form_state' => $form_state,
    'webform' => $webform,
  ];
  webform_workflows_element_webform_better_results_form_alter($data);
  $form = $data['form'];

  // Move buttons to end
  $buttons = ['submit', 'reset'];
  foreach ($buttons as $button_id) {
    if (isset($form['filter'][$button_id])) {
      $button = $form['filter'][$button_id];
      unset($form['filter'][$button_id]);
      $form['filter'][$button_id] = $button;
    }
  }
}

/**
 * Add state as a query to list builder for results.
 *
 * NOTE: this is handled by webform_better_results hooks, but also works
 * without that module via this alter.
 *
 * @param array $form
 * @param FormStateInterface $form_state
 */
function webform_workflows_element_webform_submission_filter_form_submit(array $form, FormStateInterface $form_state) {
  // If enabled, Webform Better Results will automatically pick up the hooks we use.
  // If not enabled, do it manually from within this module.
  if (Drupal::moduleHandler()->moduleExists('webform_better_results')) {
    return;
  }

  $redirect = $form_state->getRedirect();
  $query = $redirect->getOptions()['query'];

  $data = [
    'form' => $form,
    'form_state' => $form_state,
    'query' => $query,
  ];

  webform_workflows_element_webform_better_results_submit_alter($data);

  $query = $data['query'];
  $form_state = $data['form_state'];

  $query = array_filter($query);
  $form_state->setRedirect($redirect->getRouteName(), $redirect->getRouteParameters(), [
    'query' => $query,
  ]);
}

function webform_workflows_element_webform_better_results_query_alter(&$data, $queryStrings, $webform) {
  $query = $data['query'];
  $submission_storage = $data['submission_storage'];

  // Filter by workflow fields:
  $queryWorkflowElements = [];
  foreach ($queryStrings as $key => $value) {
    if (strpos($key, 'workflow-') === 0) {
      $queryWorkflowElements[str_replace('workflow-', '', $key)] = $value;
    }
  }
  foreach ($queryWorkflowElements as $element_id => $value) {
    $sub_query = Database::getConnection()
      ->select('webform_submission_data', 'sd')
      ->fields('sd', ['sid'])
      ->condition('name', $element_id)
      ->condition('property', 'workflow_state')
      ->condition('value', $value, '=');
    $submission_storage->addQueryConditions($sub_query, $webform);
    $query->condition('sid', $sub_query, 'IN');
  }

  $data['query'] = $query;
  $data['submission_storage'] = $submission_storage;
}

/**
 */
function webform_workflows_element_webform_better_results_form_alter(&$data) {
  $webform = $data['webform'];

  $workflowsManager = Drupal::service('webform_workflows_element.manager');
  $workflow_elements = $workflowsManager->getWorkflowElementsForWebform($webform);

  $form = $data['form'];
  $form_state = $data['form_state'];
  $form_state->set('workflow_elements', $workflow_elements);

  $colors = [];

  foreach ($workflow_elements as $element_id => $element) {
    $workflowType = $workflowsManager->getWorkflowType($element['#workflow']);
    $states = $workflowType ? $workflowType->getStates() : [];
    $options = ['' => ' - Any - '];
    foreach ($states as $state) {
      $options[$state->id()] = $state->label();
    }
    $colors = array_merge($colors, webform_workflows_element_get_colors_for_states($states, $element));

    $form['filter']['workflow-' . $element_id] = [
      '#type' => 'select',
      '#title' => t($element['#title']),
      '#title_display' => 'before',
      '#options' => $options,
      '#attributes' => [
        'class' => ['webform_workflows_element_filter_states'],
      ],
      '#default_value' => Drupal::request()->query->get('workflow-' . $element_id),
    ];

    if (count($colors) > 0) {
      $form['#attached']['library'][] = 'webform_workflows_element/default_colors';
      $form['#attached']['library'][] = 'webform_workflows_element/webform_workflows_element.filters';
      $form['#attached']['drupalSettings']['webform_workflows_element']['colors'] = $colors;
    }
  }

  $data = [
    'form' => $form,
    'form_state' => $form_state,
    'webform' => $webform,
  ];
}

function webform_workflows_element_webform_better_results_submit_alter(array &$data) {
  $form_state = $data['form_state'];
  $queryStrings = $data['query'];
  foreach ($form_state->get('workflow_elements') as $element_id => $element) {
    if ($form_state->getValue('workflow-' . $element_id)) {
      $queryStrings['workflow-' . $element_id] = $form_state->getValue('workflow-' . $element_id);
    }
  }
  $data['query'] = $queryStrings;
}
