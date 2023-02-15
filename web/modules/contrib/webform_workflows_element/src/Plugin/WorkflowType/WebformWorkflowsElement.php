<?php

namespace Drupal\webform_workflows_element\Plugin\WorkflowType;

use Drupal\workflows\Plugin\WorkflowTypeBase;

/**
 * Workflow field Workflow type for core workflows module.
 *
 * @WorkflowType(
 *   id = "webform_workflows_element",
 *   label = @Translation("Webform workflow"),
 *   required_states = {},
 *   forms = {
 *     "configure" = "\Drupal\webform_workflows_element\Form\WebformWorkflowsElementConfigureForm"
 *   },
 * )
 */
class WebformWorkflowsElement extends WorkflowTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getInitialState() {
    if (!isset($this->configuration['initial_state']) || $this->configuration['initial_state'] == '') {
      return FALSE;
    }

    return $this->getState($this->configuration['initial_state']);
  }
  
}
