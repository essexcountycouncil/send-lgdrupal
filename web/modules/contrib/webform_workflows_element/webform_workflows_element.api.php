<?php

/**
 * @file
 * Hooks related to the Webform Workflows Element module.
 */

// phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows to override access to a workflow element. Modify $access to allow or
 * deny.
 *
 * @param bool|NULL $access
 * @param array $context
 *   Contains: 'webform', 'element_plugin', 'account, 'workflow_element',
 *   'webform_submission'
 *
 * @return void
 */
function hook_webform_workflow_element_access_alter(bool &$access = NULL, array $context = []) {

}

/**
 * Allows to override access to a transition. Modify $access to allow or deny.
 *
 * @param bool|NULL $access
 * @param array $context
 *  Contains: 'workflow', 'webform', 'state', 'account', 'webform_submission',
 *   'transition'
 *
 * @return void
 */
function hook_webform_workflow_element_transition_access_alter(bool &$access = NULL, $context = []) {

}

/**
 * Allows to add a note for transitions on the workflow widget on the form.
 *
 * @param string|NULL $transitionMessage
 * @param array $context
 *  Contains:'webform_submission', 'form', 'form_state'
 *
 * @return void
 */
function hook_webform_workflow_element_transition_note_alter(string &$transitionMessage = NULL, $context = []) {

}
