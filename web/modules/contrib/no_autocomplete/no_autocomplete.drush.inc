<?php

/**
 * @file
 * Drush commands related to the No Autocomplete module.
 */

define('NO_AUTOCOMPLETE_GREEN_OUTPUT', "\033[1;32;40m\033[1m[%s]\033[0m");
define('NO_AUTOCOMPLETE_RED_OUTPUT', "\033[31;40m\033[1m[%s]\033[0m");

/**
 * Implements hook_drush_command().
 */
function no_autocomplete_drush_command() {
  $items['na-login'] = [
    'description' => dt('Configures the "autocomplete=off" option on the user login form.'),
    'arguments' => [
      'status' => dt('The option status (on, off).'),
    ],
    'aliases' => ['nal'],
    'examples' => [
      'na-login' => dt('Show the status for the use of the "autocomplete=off" option in the user login form.'),
      'na-login on' => dt('The user login form will use the "autocomplete=off" option.'),
      'na-login off' => dt('The user login form will don\'t use the "autocomplete=off" option.'),
    ],
  ];
  return $items;
}

/**
 * Implements hook_drush_help().
 */
function no_autocomplete_drush_help($section) {
  switch ($section) {
    case 'meta:no_autocomplete:title':
      return dt("No Autocomplete commands");

    case 'meta:no_autocomplete:summary':
      return dt("Interacts with the No Autocomplete module's functionalities.");

    case 'drush:na-login':
      return dt('Configures the "autocomplete=off" option on the user login form.');
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_no_autocomplete_na_login_validate() {
  $args = func_get_args();

  if (!_drush_no_autocomplete_validate_argument($args)) {
    return;
  }
}

/**
 * Validate the argument.
 *
 * @param array $option
 *   The option to set ('on' or 'off').
 *
 * @return bool
 *   A boolean indicating the success of the validation.
 */
function _drush_no_autocomplete_validate_argument(array $option) {
  // Check for only one argument.
  if (count($option) > 1) {
    return drush_set_error('DRUSH_NO_AUTOCOMPLETE_INVALID_ARGUMENT', dt('This command use only one argument.'));
  }

  // Available options.
  $available_options = [
    'on',
    'off',
  ];

  // Check for correct argument.
  if (isset($option[0]) && !in_array($option[0], $available_options)) {
    return drush_set_error('DRUSH_NO_AUTOCOMPLETE_INVALID_ARGUMENT', dt("You must specify as argument 'on' or 'off'"));
  }

  return TRUE;
}

/**
 * Callback for the na-login command.
 */
function drush_no_autocomplete_na_login() {
  $args = func_get_args();
  _drush_no_autocomplete_set_options('no_autocomplete_login_form', $args);
}

/**
 * Set the variable options.
 *
 * @param string $variable
 *   The variable name.
 * @param string $args
 *   The functions arguments.
 */
function _drush_no_autocomplete_set_options($variable, $args) {
  // Getting an editable config because we will get and set a value.
  $config = \Drupal::service('config.factory')->getEditable('no_autocomplete.settings');
  // Getting the values from the config.
  $config_status = $config->get($variable);
  $status = $config_status ? sprintf(NO_AUTOCOMPLETE_GREEN_OUTPUT, dt('Activated')) : sprintf(NO_AUTOCOMPLETE_RED_OUTPUT, dt('Disabled'));

  switch ($variable) {
    case 'no_autocomplete_login_form':
      $option_text = dt('login form');
      break;
  }

  if (isset($args[0])) {
    list($value, $status) = $args[0] == 'on' ? [1, dt('activated')] : [0, dt('disabled')];

    // Checking if the status is already configured.
    $already_configured = FALSE;

    if ($config_status && $value) {
      $already_configured = TRUE;
    }

    if (!$config_status && !$value) {
      $already_configured = TRUE;
    }

    if ($already_configured) {
      $message = dt('The "autocomplete=off" option on the user @option_text is already @status.', ['@status' => $status, '@option_text' => $option_text]);
      drush_log($message, 'warning');
      return;
    }

    // Saving the values in the config.
    $config->set($variable, $value);
    $config->save();

    $message = dt('You have @status the use of the "autocomplete=off" option on the user @option_text.', ['@status' => $status, '@option_text' => $option_text]);
    drush_log($message, 'success');
  }
  else {
    $message = dt('The "autocomplete=off" option on the user @option_text is: @status.', ['@status' => $status, '@option_text' => $option_text]);
    drush_print($message);
  }
}
