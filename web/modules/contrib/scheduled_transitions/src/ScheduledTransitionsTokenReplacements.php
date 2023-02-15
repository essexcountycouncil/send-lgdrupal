<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface;

/**
 * Represents strings used as replacement variables in translation or logger.
 */
class ScheduledTransitionsTokenReplacements {

  use StringTranslationTrait;

  protected ?ModerationInformationInterface $moderationInformation = NULL;
  protected ?array $cachedReplacements = NULL;

  /**
   * ScheduledTransitionsTokens constructor.
   *
   * @param \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $scheduledTransition
   *   A scheduled transition entity.
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $newRevision
   *   A new default revision.
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $latest
   *   The latest current revision.
   */
  public function __construct(protected ScheduledTransitionInterface $scheduledTransition,
    protected EntityInterface|RevisionableInterface $newRevision,
    protected EntityInterface|RevisionableInterface $latest,
  ) {
  }

  /**
   * Get variables for translation or replacement.
   *
   * @return array
   *   An array of strings keyed by replacement key.
   */
  public function getReplacements() {
    if (isset($this->cachedReplacements)) {
      return $this->cachedReplacements;
    }

    $entityRevisionId = $this->newRevision->getRevisionId();

    // getWorkflowForEntity only supports Content Entities, this can be removed
    // if Scheduled Transitions supports non CM workflows in the future.
    $states = [];
    if ($this->latest instanceof ContentEntityInterface) {
      $workflow = $this->moderationInformation()->getWorkflowForEntity($this->latest);
      $workflowPlugin = $workflow->getTypePlugin();
      $states = $workflowPlugin->getStates();
    }

    $originalNewRevisionState = $states[$this->newRevision->moderation_state->value ?? ''] ?? NULL;
    $originalLatestState = $states[$this->latest->moderation_state->value ?? ''] ?? NULL;
    $newState = $states[$this->scheduledTransition->getState()] ?? NULL;

    return $this->cachedReplacements = [
      'from-revision-id' => $entityRevisionId,
      'from-state' => $originalNewRevisionState ? $originalNewRevisionState->label() : $this->t('- Unknown state -'),
      'to-state' => $newState ? $newState->label() : $this->t('- Unknown state -'),
      'latest-revision-id' => $this->latest->getRevisionId(),
      'latest-state' => $originalLatestState ? $originalLatestState->label() : $this->t('- Unknown state -'),
    ];
  }

  /**
   * Moderation information service.
   *
   * @return \Drupal\content_moderation\ModerationInformationInterface
   *   Moderation information service.
   */
  protected function moderationInformation(): ModerationInformationInterface {
    // @phpstan-ignore-next-line
    return $this->moderationInformation ?? \Drupal::service('content_moderation.moderation_information');
  }

  /**
   * Sets moderation information service.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   Moderation information service.
   */
  public function setModerationInformation(ModerationInformationInterface $moderationInformation): void {
    $this->moderationInformation = $moderationInformation;
  }

}
