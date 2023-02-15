<?php

namespace Drupal\webform_workflows_element_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Drupal\webform\Entity\Webform;
use Drupal\workflows\Entity\Workflow;

/**
 * Filter based on value of a composite of a webform submission.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("webform_workflows_element_state")
 */
class WebformWorkflowsElementState extends FilterPluginBase {

  /**
   * Applying query filter. If you turn on views query debugging you should see
   * these clauses applied. If the filter is optional, and nothing is selected,
   * this code will never be called.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function query() {
    $this->ensureMyTable();

    $stateIds = [];
    if (empty($this->value)) {
      return;
    }
    else {
      $stateIds = $this->value;
    }

    $configuration = [
      'table' => 'webform_submission_data',
      'field' => 'sid',
      'left_table' => 'webform_submission',
      'left_field' => 'sid',
      'adjusted' => TRUE,
      'type' => 'LEFT',
    ];
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->query->addRelationship('wsd_state', $join, 'webform_submission_data');

    $this->query->addWhere('state', 'wsd_state.webform_id', $this->options['webform_id']);
    $this->query->addWhere('state', 'wsd_state.name', $this->options['workflow_element_id']);
    $this->query->addWhere('state', 'wsd_state.property', 'workflow_state');
    $this->query->addWhere('state', 'wsd_state.value', $stateIds, 'IN');
  }

  public function adminSummary() {
    if (!$this->options['webform_id'] || !$this->options['workflow_element_id']) {
      return '';
    }

    $webform = Webform::load($this->options['webform_id']);
    $webform_text = $this->t('webform') . ': ' . $webform->label();
    $element_text = $this->t('workflow element') . ': ' . $this->options['workflow_element_id'];
    return $webform_text . ', ' . $element_text;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['webform_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'webform',
      '#title' => $this->t('Webform'),
      '#default_value' => $this->options['webform_id'] ? Webform::load($this->options['webform_id']) : NULL,
      '#required' => TRUE,
    ];

    $form['workflow_element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workflow element ID'),
      '#default_value' => $this->options['workflow_element_id'] ?: 'workflow',
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $form['value'] = !empty($form['value']) ? $form['value'] : [];
    parent::buildExposedForm($form, $form_state);

    if (!$this->options['webform_id'] || !$this->options['workflow_element_id']) {
      return;
    }

    $filter_id = $this->getFilterId();
    // Field which really filters.
    $form[$filter_id] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    $webform = Webform::load($this->options['webform_id']);
    $workflowElement = $webform->getElement($this->options['workflow_element_id']);
    $workflow = Workflow::load($workflowElement['#workflow']);
    $states = $workflow->getTypePlugin()->getStates();

    $options = [
      '' => t('All'),
    ];
    foreach ($states as $state_id => $state) {
      $options[$state_id] = $state->label();
    }

    $form['states'] = [
      '#title' => $this->t('Workflow state(s)'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#tags' => TRUE,
      '#options' => $options,
      '#attributes' => [
        'class' => ['webform_workflows_element_filter_states'],
      ],
      '#default_value' => !empty($this->value['states']) ? $this->value['states'] : '',
    ];

    $colors = webform_workflows_element_get_colors_for_states($states, $workflowElement);

    if (count($colors) > 0) {
      $form['#attached']['library'][] = 'webform_workflows_element/default_colors';
      $form['#attached']['library'][] = 'webform_workflows_element/webform_workflows_element.filters';
      $form['#attached']['drupalSettings']['webform_workflows_element']['colors'] = $colors;
    }
  }

  /**
   * This method returns the ID of the fake field which contains this plugin.
   *
   * It is important to put this ID to the exposed field of this plugin for the
   * following reasons: a) To avoid problems with
   * FilterPluginBase::acceptExposedInput function b) To allow this filter to
   * be printed on twig templates with {{ form.date_range_picker_filter }}
   *
   * @return string
   *   ID of the field which contains this plugin.
   */
  private function getFilterId() {
    return $this->options['expose']['identifier'];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['expose'])) {
      return TRUE;
    }

    $input[$this->options['expose']['identifier']] = $input['states'];

    return parent::acceptExposedInput($input);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['webform_id'] = ['default' => ''];
    $options['workflow_element_id'] = ['default' => ''];

    return $options;
  }

}
