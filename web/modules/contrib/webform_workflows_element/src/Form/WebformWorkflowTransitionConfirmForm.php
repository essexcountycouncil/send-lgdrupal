<?php

namespace Drupal\webform_workflows_element\Form;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Defines a confirmation form to transition a submission.
 */
class WebformWorkflowTransitionConfirmForm extends ConfirmFormBase {

  /**
   * Webform.
   *
   * @var WebformInterface
   */
  protected WebformInterface $webform;

  /**
   * Webform submission.
   *
   * @var WebformSubmissionInterface
   */
  protected WebformSubmissionInterface $webform_submission;

  /**
   * Workflow element.
   *
   * @var array|NULL
   */
  protected ?array $element;

  /**
   * Workflow.
   *
   * @var WorkflowInterface
   */
  protected WorkflowInterface $workflow;

  /**
   * Transition.
   *
   * @var TransitionInterface
   */
  protected TransitionInterface $transition;

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?TranslatableMarkup {
    return $this->t('Do you want to perform "@transition" on submission @id?', [
      '@transition' => $this->transition->label(),
      '@id' => $this->webform_submission->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): TranslatableMarkup {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL, string $workflow_element = NULL, string $transition = NULL): array {
    // Set variables:
    $this->webform_submission = $webform_submission;
    $this->webform = $webform;

    /** @var \Drupal\webform_workflows_element\Service\WebformWorkflowsManager $workflows_manager */
    $workflows_manager = Drupal::service('webform_workflows_element.manager');
    $this->element = $workflows_manager->getWorkflowElementsForWebform($this->webform)[$workflow_element] ?? NULL;
    if (!$this->element) {
      $this->throwFormError($form, $this->t('Cannot load element.', [
      ]));
      return $form;
    }

    $this->workflow = $workflows_manager->getWorkflow($this->element['#workflow']);
    if (!$this->workflow) {
      $this->throwFormError($form, $this->t('Cannot load workflow.', [
      ]));
      return $form;
    }

    $this->transition = $this->workflow->getTypePlugin()
      ->getTransition($transition);

    $form = parent::buildForm($form, $form_state);

    $form['log_public'] = [
      '#title' => t('Log message for submitter'),
      '#type' => $this->element['#log_public_setting'] != 'Disabled' ? 'textarea' : 'hidden',
      '#rows' => 2,
      '#required' => $this->element['#log_public_setting'] === 'Required',
    ];

    $form['log_admin'] = [
      '#title' => t('Log message - admin only'),
      '#type' => $this->element['#log_admin_setting'] != 'Disabled' ? 'textarea' : 'hidden',
      '#rows' => 2,
      '#required' => $this->element['#log_admin_setting'] === 'Required',
    ];

    return $form;
  }

  public function throwFormError(&$form, $message) {
    $form['actions']['submit']['#access'] = FALSE;
    $form['actions']['cancel']['#title'] = $this->t('Cancel');
    $form['description']['#markup'] = $message;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission = WebformSubmission::load($this->webform_submission->id());

    /** @var \Drupal\webform_workflows_element\Service\WebformWorkflowsManager $workflows_manager */
    $workflows_manager = Drupal::service('webform_workflows_element.manager');
    foreach ($workflows_manager->getWorkflowElementsForWebform($this->webform) as $element_id => $element) {
      if ($element['#workflow'] === $this->workflow->id()) {
        $data = $this->webform_submission->getElementData($element_id);
        $data['transition'] = $this->transition->id();
        if ($log_public = $form_state->getValue('log_public')) {
          $data['log_public'] = $log_public;
        }
        if ($log_admin = $form_state->getValue('log_admin')) {
          $data['log_admin'] = $log_admin;
        }
        $this->webform_submission->setElementData($element_id, $data);
        $workflows_manager->runTransitionOnElementValue($this->webform_submission, $element_id);

        \Drupal::messenger()
          ->addMessage(t("%transition run on webform submission @submission_id.", [
            '%transition' => $this->transition->label(),
            '%transition_id' => $this->transition->id(),
            '@submission_id' => $this->webform_submission->id(),
          ]), 'status');
      }
    }

    $submission->save();

    $url = $this->webform_submission ? $this->webform_submission->toUrl() : $this->getCancelUrl();

    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.webform.results_submissions', [
      'webform' => $this->webform->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return "webform_workflow_transition_confirm_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Perform "@transition" on submission @id?', [
      '@transition' => $this->transition->label(),
      '@id' => $this->webform_submission->id(),
    ]);
  }

  /**
   * @throws \Exception
   */
  public function checkAccess(WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL, string $workflow_element = NULL, string $transition = NULL) {
    /** @var \Drupal\webform_workflows_element\Service\WebformWorkflowsManager $workflows_manager */
    $workflows_manager = Drupal::service('webform_workflows_element.manager');
    $element = $workflows_manager->getWorkflowElementsForWebform($webform)[$workflow_element] ?? NULL;
    $workflow = $workflows_manager->getWorkflow($element['#workflow']);
    $transition = $workflow->getTypePlugin()
      ->getTransition($transition);

    // Check access to transition:
    /** @var \Drupal\webform_workflows_element\Service\WebformWorkflowsManager $workflows_manager */
    $workflows_manager = Drupal::service('webform_workflows_element.manager');
    $account = Drupal::currentUser();
    $access = $workflows_manager->checkAccessForSubmissionAndTransition($workflow, $account, $webform, $transition, NULL, $webform_submission);
    return AccessResult::allowedIf($access);
  }

}
