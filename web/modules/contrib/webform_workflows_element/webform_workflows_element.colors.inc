<?php

/**
 * Get options for select dropdown of colors.
 *
 * We store the name as that's what the user saw to choose;
 * the class may change but "red" should stay meaning "red".
 *
 * @return array|null
 *   Array of names also keyed by name.
 */
function webform_workflows_element_get_color_options(): ?array {
  $options = [];
  $values = webform_workflows_element_get_color_options_values();
  if ($values) {
    foreach ($values as $name => $class) {
      $options[$name] = $name;
    }
  }
  return $options;
}

/**
 * Load available color classes for webforms.
 *
 * @return array|null
 *   Array of classes keyed by name
 * @see WebformAdminConfigWorkflowsForm
 *
 * @see webform_workflows_element.settings.schema.yml
 */
function webform_workflows_element_get_color_options_values(): ?array {
  $config = Drupal::config('webform_workflows_element.settings');
  if ($colors = $config->get('ui.color_options')) {
    $color_options = [];
    $colors = str_replace(["\r", "\n", "\n\n"], "\n", $colors);
    $lines = explode("\n", $colors);
    foreach ($lines as $line) {
      $values = explode('|', $line);
      $color_options[$values[0]] = $values[1];
    }
    return $color_options;
  }
  return NULL;
}

function webform_workflows_element_get_colors_for_states(array $states, $workflowElement): array {
  $colors = [];

  foreach ($states as $state) {
    if (isset($workflowElement['#state_' . $state->id() . '_color'])) {
      $color_name = $workflowElement['#state_' . $state->id() . '_color'];
      $color_options = webform_workflows_element_get_color_options_values();
      $class = $color_options[$color_name] ?? '';
      $colors[$state->id()] = $class;
      $colors[$state->label()] = $class;
    }
  }
  return $colors;
}

/**
 * Return CSS class for a state in an element.
 *
 * @param mixed $element
 * @param mixed $state_id
 *
 * @return string
 *   CSS class. Make sure it is always applied with the additional 'with-color'
 *   class.
 */
function webform_workflows_element_get_color_class_for_state_from_element($element, $state_id): ?string {
  if (isset($element['#state_' . $state_id . '_color']) && $color_name = $element['#state_' . $state_id . '_color']) {
    $options = webform_workflows_element_get_color_options_values();
    return $options[$color_name] ?? NULL;
  }
  return NULL;
}
