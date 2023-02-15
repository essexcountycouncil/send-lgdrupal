<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions\Form\Entity;

use Drupal\Component\Utility\Xss;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\Render\Markup;
use Drupal\scheduled_transitions\Entity\ScheduledTransition;
use Drupal\scheduled_transitions\ScheduledTransitionsUtilityInterface;
use Drupal\workflows\Transition;
use Drupal\workflows\WorkflowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Scheduled transitions add form.
 */
class ScheduledTransitionAddForm extends ContentEntityForm {

  /**
   * Constant indicating the form key representing: latest revision.
   *
   * @internal will be made protected when PHP version is raised.
   */
  const LATEST_REVISION = 'latest_revision';

  protected DateFormatterInterface $dateFormatter;
  protected ModerationInformationInterface $moderationInformation;
  protected StateTransitionValidationInterface $stateTransitionValidation;
  protected LanguageManagerInterface $languageManager;
  protected ScheduledTransitionsUtilityInterface $scheduledTransitionsUtility;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->moderationInformation = $container->get('content_moderation.moderation_information');
    $instance->stateTransitionValidation = $container->get('content_moderation.state_transition_validation');
    $instance->languageManager = $container->get('language_manager');
    $instance->scheduledTransitionsUtility = $container->get('scheduled_transitions.utility');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $account = $this->currentUser();
    $form['scheduled_transitions']['#theme'] = 'scheduled_transitions_form_add';

    $entity = $this->getEntity();

    $header = [];
    $header['revision_id'] = $this->t('Revision');
    $header['state'] = $this->t('State');
    if ($entity instanceof RevisionLogInterface) {
      $header['revision_time'] = $this->t('Saved on');
      $header['revision_author'] = $this->t('Saved by');
      $header['revision_log'] = $this->t('Log');
    }

    $newMetaWrapperId = 'new-meta-wrapper';

    $input = $form_state->getUserInput();
    $revisionOptions = $this->getRevisionOptions($entity);

    // Use the selected option (if form is being rebuilt from AJAX), otherwise
    // select latest revision if it exists.
    $revision = $input['revision'] ??
      (isset($revisionOptions[static::LATEST_REVISION]) ? static::LATEST_REVISION : NULL);

    $form['scheduled_transitions']['revision'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#caption' => $this->t('Select which revision you wish to move to a new state.'),
      '#options' => $revisionOptions,
      '#multiple' => FALSE,
      '#footer' => [
        [
          [
            'colspan' => count($header) + 1,
            'data' => ['#plain_text' => $this->t('Revisions are ordered from newest to oldest.')],
          ],
        ],
      ],
      '#process' => [
        [Tableselect::class, 'processTableselect'],
        '::revisionProcess',
      ],
      '#new_meta_wrapper_id' => $newMetaWrapperId,
      '#default_value' => $revision,
    ];

    $form['scheduled_transitions']['new_meta'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $newMetaWrapperId,
        'class' => ['container-inline'],
      ],
    ];

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $workflowPlugin = $workflow->getTypePlugin();

    // Populate options with nothing.
    if (is_numeric($revision) && $revision > 0) {
      $entityStorage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      $entityRevision = $entityStorage->loadRevision($revision);
      $toTransitions = $this->stateTransitionValidation
        ->getValidTransitions($entityRevision, $this->currentUser());
    }
    elseif (is_string($revision)) {
      // Show all transitions as we cannot be sure what will be available.
      // Cannot use getValidTransitions since it is only valid for the current
      // state of the entity passed to it:
      $toTransitions = array_filter(
        $workflowPlugin->getTransitions(),
        fn (Transition $transition) => $account->hasPermission('use ' . $workflow->id() . ' transition ' . $transition->id()),
      );
    }

    if (isset($toTransitions)) {
      $transitionOptions = [];
      foreach ($toTransitions as $toTransition) {
        $transitionOptions[$toTransition->id()] = $toTransition->label();
      }

      $form['scheduled_transitions']['new_meta']['transition_help']['#markup'] = $this->t('<strong>Execute transition</strong>');
      $form['scheduled_transitions']['new_meta']['transition'] = [
        '#type' => 'select',
        '#options' => $transitionOptions,
        '#empty_option' => $this->t('- Select -'),
        '#required' => TRUE,
      ];

      $form['scheduled_transitions']['new_meta']['on_help']['#markup'] = $this->t('<strong>on date</strong>');
      $form['scheduled_transitions']['new_meta']['on'] = [
        '#type' => 'datetime',
        '#default_value' => new \DateTime(),
        '#required' => TRUE,
      ];
    }
    else {
      $form['scheduled_transitions']['new_meta']['transition_help']['#markup'] = $this->t('Select a revision above');
    }

    $form['scheduled_transitions']['to_options'] = [
      '#type' => 'container',
    ];

    if (isset($toTransitions) && count($toTransitions) > 0) {
      // Its too difficult to have a checkbox with default TRUE with conditional
      // existence, as AJAX reloads, will sometimes show the checkbox as
      // unchecked. See https://www.drupal.org/project/drupal/issues/1100170.
      // Instead show this checkbox depending on value of other fields. The
      // checkbox will always be present therefore preserving its state.
      $conditions = [];
      foreach ($toTransitions as $transition) {
        if ($transition->to()->isDefaultRevisionState()) {
          $conditions[] = [':input[name="transition"]' => ['value' => $transition->id()]];
        }
      }

      $form['scheduled_transitions']['to_options']['recreate_non_default_head'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Recreate pending revision'),
        '#description' => $this->t('Before creating this revision, check if there is any pending work. If so then recreate it. Regardless of choice, revisions are safely retained in history, and can be reverted manually.'),
        '#default_value' => TRUE,
        '#states' => [
          'visible' => $conditions,
        ],
      ];
    }

    $form['scheduled_transitions']['revision_metadata'] = $this->getRevisionMetadata($form_state, $workflow, $entity, $entityRevision ?? NULL);

    return $form;
  }

  /**
   * Add AJAX functionality to revision radios.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete form.
   *
   * @return array
   *   The modified element.
   */
  public function revisionProcess(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    // Add AJAX to tableselect.
    $newMetaWrapperId = $element['#new_meta_wrapper_id'];
    foreach (Element::children($element) as $key) {
      $element[$key]['#ajax'] = [
        'event' => 'change',
        'callback' => '::ajaxCallbackNewMeta',
        'wrapper' => $newMetaWrapperId,
        'progress' => [
          'type' => 'fullscreen',
        ],
        'effect' => 'fade',
      ];
    }
    return $element;
  }

  /**
   * Ajax handler for new meta container.
   */
  public function ajaxCallbackNewMeta($form, FormStateInterface $form_state): array {
    return $form['scheduled_transitions']['new_meta'];
  }

  /**
   * Ajax handler for revision log preview.
   */
  public function reloadRevisionLogPreview(array &$form, FormStateInterface $form_state): array {
    return $form['scheduled_transitions']['revision_metadata']['revision_log']['preview'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (empty($form_state->getValue('revision'))) {
      $form_state->setError($form['scheduled_transitions']['revision'], $this->t('Revision must be selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $entity = $this->getEntity();
    $options = [];

    if ($form_state->getValue('recreate_non_default_head')) {
      $options[ScheduledTransition::OPTION_RECREATE_NON_DEFAULT_HEAD] = TRUE;
    }

    $revisionOption = $form_state->getValue('revision');
    $entityRevisionId = 0;
    if ($revisionOption === static::LATEST_REVISION) {
      $options[ScheduledTransition::OPTION_LATEST_REVISION] = TRUE;
    }
    else {
      $entityRevisionId = $revisionOption;
    }

    /** @var array|null $revisionMetadataValues */
    $revisionMetadataValues = $form_state->getValue(['revision_metadata']);
    if ($revisionMetadataValues['revision_log']['override'] ?? NULL) {
      $message = $revisionMetadataValues['revision_log']['custom']['message'] ?? '';
      $options['revision_log_override'] = TRUE;
      $options['revision_log'] = $message;
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $transition = $form_state->getValue(['transition']);
    $workflowPlugin = $workflow->getTypePlugin();
    $newState = $workflowPlugin->getTransition($transition)->to()->id();

    /** @var \Drupal\Core\Datetime\DrupalDateTime $onDate */
    $onDate = $form_state->getValue(['on']);

    $scheduledTransitionStorage = $this->entityTypeManager->getStorage('scheduled_transition');
    /** @var \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $scheduledTransition */
    $scheduledTransition = $scheduledTransitionStorage->create([
      'entity' => [$entity],
      'entity_revision_id' => $entityRevisionId,
      'entity_revision_langcode' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
      'author' => [$this->currentUser()->id()],
      'workflow' => $workflow->id(),
      'moderation_state' => $newState,
      'transition_on' => $onDate->getTimestamp(),
      'options' => [
        $options,
      ],
    ]);
    $scheduledTransition->save();

    $this->messenger()->addMessage($this->t('Scheduled a transition for @date', [
      '@date' => $this->dateFormatter->format($onDate->getTimestamp()),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions['submit']['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Schedule transition'),
      '#submit' => ['::submitForm'],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    // Not saving.
  }

  /**
   * Get revisions for an entity as options for a tableselect.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Get revisions for this entity.
   *
   * @return array
   *   An array of options suitable for a tableselect element.
   */
  protected function getRevisionOptions(EntityInterface $entity): array {
    $entityTypeId = $entity->getEntityTypeId();
    $entityStorage = $this->entityTypeManager->getStorage($entityTypeId);

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $workflowPlugin = $workflow->getTypePlugin();
    $workflowStates = $workflowPlugin ? $workflowPlugin->getStates() : [];

    $revisionIds = $this->scheduledTransitionsUtility->getTargetRevisionIds(
      $entity,
      $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId()
    );

    $entityRevisions = array_map(function (string $revisionId) use ($entityStorage): EntityInterface {
      $revision = $entityStorage->loadRevision($revisionId);
      // When the entity is translatable, load the translation for the current
      // language.
      if ($revision instanceof TranslatableInterface) {
        $revision = $revision->getTranslation($this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId());
      }
      return $revision;
    }, array_combine($revisionIds, $revisionIds));

    // When the entity is translatable, every revision contains a copy for every
    // translation. We only want to show the revisions that affected the
    // translation for the current language.
    $entityRevisions = array_filter(
      $entityRevisions,
      fn (EntityInterface $revision) => $revision instanceof TranslatableRevisionableInterface ? $revision->isRevisionTranslationAffected() : TRUE,
    );

    $options = array_map(
      function (EntityInterface $entityRevision) use ($workflowStates): array {
        /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $entityRevision */
        $option = [];
        $revisionTArgs = ['@revision_id' => $entityRevision->getRevisionId()];

        // Dont add the arg to toLink in case this particular entity has
        // overwritten the default value of the param.
        $toLinkArgs = [$this->t('#@revision_id', $revisionTArgs)];
        if ($entityRevision->hasLinkTemplate('revision')) {
          $toLinkArgs[] = 'revision';
        }
        $revisionLink = $entityRevision->toLink(...$toLinkArgs);
        $revisionCell = $revisionLink->toRenderable();
        $revisionCell['#attributes'] = [
          'target' => '_blank',
        ];

        $option['revision_id']['data'] = $revisionCell;
        $moderationState = $workflowStates[$entityRevision->moderation_state->value] ?? NULL;
        $option['state']['data'] = $moderationState ? $moderationState->label() : $this->t('- Unknown state -');
        if ($entityRevision instanceof RevisionLogInterface) {
          $option['revision_time']['data']['#plain_text'] = $this->dateFormatter
            ->format($entityRevision->getRevisionCreationTime());
          $revisionUser = $entityRevision->getRevisionUser();
          if ($revisionUser) {
            $option['revision_author']['data'] = $this->moduleHandler->moduleExists('user') ? [
              '#theme' => 'username',
              '#account' => $revisionUser,
            ] : $revisionUser->toLink();
          }
          else {
            $option['revision_author']['data'] = $this->t('- Missing user -');
          }

          if ($revisionLog = $entityRevision->getRevisionLogMessage()) {
            $option['revision_log']['data'] = [
              '#markup' => $revisionLog,
              '#allowed_tags' => Xss::getHtmlTagList(),
            ];
          }
          else {
            $option['revision_log']['data'] = $this->t('<em>- None -</em>');
          }
        }

        return $option;
      },
      $entityRevisions
    );

    $options = [
      static::LATEST_REVISION => [
        'revision_id' => [
          'data' => $this->t('Latest revision'),
        ],
        'state' => [
          'data' => $this->t('Automatically determines the latest revision at time of transition.'),
          'colspan' => $entity instanceof RevisionLogInterface ? 4 : 1,
        ],
      ],
    ] + $options;

    return $options;
  }

  /**
   * Generate revision metadata section of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\workflows\WorkflowInterface|null $workflow
   *   The workflow.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being transitioned.
   * @param \Drupal\Core\Entity\EntityInterface|null $entityRevision
   *   The revision being transitioned, if available.
   *
   * @return array
   *   A form array.
   */
  protected function getRevisionMetadata(FormStateInterface $form_state, ?WorkflowInterface $workflow, EntityInterface $entity, ?EntityInterface $entityRevision): array {
    $form = [
      '#tree' => TRUE,
    ];

    $settings = $this->configFactory->get('scheduled_transitions.settings');
    $form['revision_log'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Revision Log'),
      '#access' => (bool) $settings->get('message_override'),
    ];

    $overrideCheckboxName = 'revision_metadata[revision_log][override]';
    $form['revision_log']['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override revision log message'),
      '#description' => $this->t('Override revision log message, otherwise a revision log will be generated automatically.'),
      '#name' => $overrideCheckboxName,
      '#ajax' => [
        'callback' => '::reloadRevisionLogPreview',
        'wrapper' => 'revision-log-preview',
        'effect' => 'fade',
      ],
    ];

    $form['revision_log']['custom'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [':input[name="' . $overrideCheckboxName . '"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['revision_log']['custom']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision log'),
      '#description' => $this->t('The revision log applied to the newly created revision.'),
    ];

    $form['revision_log']['custom']['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [
        'scheduled-transitions',
      ],
    ];

    // This shows the Revision log if it was executed at this time with the
    // current selected contexts.
    $form['revision_log']['preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Preview'),
      '#prefix' => '<div id="revision-log-preview">',
      '#suffix' => '</div>',
    ];

    $form['revision_log']['preview_reload'] = [
      // @todo Hide this button later, but click and debounce automatically.
      // when form elements change.
      '#type' => 'button',
      '#value' => $this->t('Reload preview'),
      '#name' => 'revision_log_preview_reload',
      '#ajax' => $form['revision_log']['override']['#ajax'],
      // Trick handleErrorsWithLimitedValidation into giving us our values,
      // same as if a non button (e.g select) added #ajax.
      '#limit_validation_errors' => NULL,
    ];

    $scheduledTransitionStorage = $this->entityTypeManager->getStorage('scheduled_transition');
    /** @var \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $scheduledTransition */
    // Create an unused Scheduled Transition.
    $scheduledTransition = $scheduledTransitionStorage->create([]);
    $transitionValue = $form_state->getValue(['transition']);
    if ($transitionValue) {
      $workflowPlugin = $workflow->getTypePlugin();
      $newState = $workflowPlugin->getTransition($transitionValue)->to()->id();
      $scheduledTransition->setState($workflow, $newState);
    }
    /** @var array|null $revisionMetadataValues */
    $revisionMetadataValues = $form_state->getValue(['revision_metadata']);
    if ($revisionMetadataValues['revision_log']['override'] ?? NULL) {
      $message = $revisionMetadataValues['revision_log']['custom']['message'] ?? '';
      $scheduledTransition->setOptions([
        'revision_log_override' => TRUE,
        'revision_log' => $message,
      ]);
    }
    // Use entity revision if determinable, otherwise the default.
    $rawRevisionLog = $this->scheduledTransitionsUtility->generateRevisionLog($scheduledTransition, $entityRevision ?? $entity);
    $form['revision_log']['preview']['revision_log'] = [
      '#type' => 'inline_template',
      '#template' => '<blockquote>{{ revision_log }}</blockquote>',
      '#context' => [
        'revision_log' => Markup::create(Xss::filter($rawRevisionLog, Xss::getHtmlTagList())),
      ],
    ];

    return $form;
  }

}
