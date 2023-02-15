<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface;

/**
 * Utilities for Scheduled Transitions module.
 */
interface ScheduledTransitionsUtilityInterface {

  /**
   * Get scheduled transitions for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface[]
   *   An array of scheduled transitions.
   */
  public function getTransitions(EntityInterface $entity): array;

  /**
   * Get list of entity type/bundles scheduled transitions can work with.
   *
   * @return array
   *   Arrays of bundles keyed by entity type.
   */
  public function getApplicableBundles(): array;

  /**
   * Get list of entity type/bundles scheduled transitions are enabled on.
   *
   * @return array
   *   Arrays of bundles keyed by entity type.
   */
  public function getBundles(): array;

  /**
   * Get potential revisions which can be transitioned to.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity a transition is created for.
   * @param string $language
   *   The language code.
   *
   * @return array
   *   An unordered array of revision IDs.
   */
  public function getTargetRevisionIds(EntityInterface $entity, string $language): array;

  /**
   * Generates a revision log for a ready to save revision.
   *
   * @param \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $scheduledTransition
   *   The scheduled transition for the associated revision.
   * @param \Drupal\Core\Entity\RevisionLogInterface $newRevision
   *   The entity a transition is created for.
   *
   * @return string
   *   A revision log with replaced tokens.
   *
   * @throws \Drupal\scheduled_transitions\Exception\ScheduledTransitionMissingEntity
   *   Thrown if latest revision of a entity could not be determined.
   */
  public function generateRevisionLog(ScheduledTransitionInterface $scheduledTransition, RevisionLogInterface $newRevision): string;

}
