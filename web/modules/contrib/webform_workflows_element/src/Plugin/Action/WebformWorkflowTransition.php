<?php

namespace Drupal\webform_workflows_element\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Annotation\Action;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_workflows_element\Service\WebformWorkflowsManager;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Changes moderation_state of an entity.
 *
 * @Action(
 *   id = "webform_workflow_transition",
 *   label = @Translation("Perform workflow transition on submissions"),
 *   type = "webform_submission"
 * )
 */
class WebformWorkflowTransition extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;

  /**
   * The webform workflows manager service.
   *
   * @var \Drupal\webform_workflows_element\Service\WebformWorkflowsManager
   */
  protected $workflowsManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Moderation state change constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\webform_workflows_element\Service\WebformWorkflowsManager $workflows_manager
   *   The webform workflows manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WebformWorkflowsManager $workflows_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->workflowsManager = $workflows_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('webform_workflows_element.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'workflow' => NULL,
      'transition' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $workflow_options = [];

    $workflows = Workflow::loadMultipleByType('webform_workflows_element');
    foreach ($workflows as $workflow) {
      $workflow_options[$workflow->id()] = $workflow->label();
    }

    if (!$default_workflow = $form_state->getValue('workflow')) {
      if (!empty($this->configuration['workflow'])) {
        $default_workflow = $this->configuration['workflow'];
      }
      else {
        $default_workflow = key($workflow_options);
      }
    }

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflow_options,
      '#default_value' => $default_workflow,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [static::class, 'configurationFormAjax'],
        'wrapper' => 'edit-state-wrapper',
      ],
    ];


    $form['workflow_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change workflow'),
      '#limit_validation_errors' => [['workflow']],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#submit' => [[static::class, 'configurationFormAjaxSubmit']],
    ];

    if ($default_workflow) {
      $transition_options = [];
      foreach ($workflows[$default_workflow]->getTypePlugin()
                 ->getTransitions() as $transition) {
        $transition_options[$transition->id()] = $this->t('Perform @transition transition', ['@transition' => $transition->label()]);
      }

      $form['state-wrapper'] = [
        '#type' => 'container',
        '#id' => 'edit-state-wrapper',
      ];

      $form['state-wrapper']['transition'] = [
        '#type' => 'select',
        '#title' => $this->t('Transition'),
        '#options' => $transition_options,
        '#default_value' => $this->configuration['transition'],
        '#required' => TRUE,
      ];
    }

    $form['element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element key'),
      '#description' => $this->t('Optionally provide the key for a workflow element to limit the transition.'),
      '#default_value' => $this->configuration['element'] ?? '',
    ];

    $form['log_public'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Log message'),
      '#default_value' => $this->configuration['log_public'] ?? '',
    ];

    return $form;

  }

  /**
   * Ajax callback for the configuration form.
   *
   * @see static::buildConfigurationForm()
   */
  public static function configurationFormAjax($form, FormStateInterface $form_state) {
    return $form['state-wrapper'];
  }

  /**
   * Submit configuration for the non-JS case.
   *
   * @see static::buildConfigurationForm()
   */
  public static function configurationFormAjaxSubmit($form, FormStateInterface $form_state) {
    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['workflow'] = $form_state->getValue('workflow');
    $this->configuration['transition'] = $form_state->getValue('transition');
    $this->configuration['element'] = $form_state->getValue('element');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if (!empty($this->configuration['workflow'])) {
      $this->addDependency('config', 'workflows.workflow.' . $this->configuration['workflow']);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function execute(WebformSubmissionInterface $entity = NULL) {
    $webform = $entity->getWebform();
    $workflow_elements = $this->workflowsManager->getWorkflowElementsForWebform($webform);
    foreach ($workflow_elements as $element_id => $element) {
      if ($target_element_id = $this->configuration['element'] ?: NULL) {
        if ($element_id != $target_element_id) {
          continue;
        }
      }

      $updatedData = $this->workflowsManager->runTransition($entity, $element_id, $this->configuration['transition'], $this->configuration['log_public'] ?: NULL, TRUE);
      if ($updatedData) {
        $entity->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$object instanceof WebformSubmissionInterface) {
      $result = AccessResult::forbidden('Not a valid webform submission.');
      return $return_as_object ? $result : $result->isAllowed();
    }

    $workflow_elements = $this->workflowsManager->getWorkflowElementsForWebform($object->getWebform());
    if (!$workflow_elements) {
      $result = AccessResult::forbidden('No workflow element found for the webform.');
      return $return_as_object ? $result : $result->isAllowed();
    }

    $has_valid_workflow = FALSE;
    $has_valid_transition = FALSE;

    $target_element_id = $this->configuration['element'] ?: NULL;
    $has_target_element = $target_element_id ? FALSE : TRUE;

    $webform = $object->getWebform();

    foreach ($workflow_elements as $element_id => $element) {
      if ($element_id == $target_element_id) {
        $has_target_element = TRUE;
      }

      $workflow_id = $element['#workflow'] ?? NULL;
      $this_workflow_valid = $workflow_id === $this->configuration['workflow'];
      if ($this_workflow_valid) {
        $has_valid_workflow = TRUE;
      }
      else {
        continue;
      }

      $element_data = $object->getElementData($element_id);
      $current_state_id = $element_data['workflow_state'];
      $valid_transitions = $this->workflowsManager->getAvailableTransitionsForWorkflow($workflow_id, $current_state_id, $account, $webform);
      if (in_array($this->configuration['transition'], array_keys($valid_transitions))) {
        $has_valid_transition = TRUE;
      }
    }

    if (!$has_target_element) {
      $result = AccessResult::forbidden('No workflow element found for the webform matching the element specified.');
      return $return_as_object ? $result : $result->isAllowed();
    }

    if (!$has_valid_workflow) {
      $result = AccessResult::forbidden('Not a valid workflow for this submission.');
      return $return_as_object ? $result : $result->isAllowed();
    }

    if (!$has_valid_transition) {
      $result = AccessResult::forbidden('Not a valid transition for this submission.');
      return $return_as_object ? $result : $result->isAllowed();
    }

    // If still allowed, need submission update access.
    $access = $object->access('update', $account, TRUE);
    $result = AccessResult::allowed()->andIf($access);
    return $return_as_object ? $result : $result->isAllowed();
  }

}