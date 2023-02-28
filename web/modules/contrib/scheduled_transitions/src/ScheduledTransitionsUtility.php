<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface;
use Drupal\scheduled_transitions\Exception\ScheduledTransitionMissingEntity;
use Drupal\scheduled_transitions\Form\ScheduledTransitionsSettingsForm as SettingsForm;

/**
 * Utilities for Scheduled Transitions module.
 */
class ScheduledTransitionsUtility implements ScheduledTransitionsUtilityInterface {

  /**
   * Cache bin ID for enabled bundled cache.
   */
  protected const CID_SCHEDULED_TRANSITIONS_BUNDLES = 'scheduled_transitions_enabled_bundles';

  /**
   * Query tag to alter target revisions.
   */
  public const QUERY_TAG_TARGET_REVISIONS = 'scheduled_transitions_target_revisions';

  /**
   * Constructs a new ScheduledTransitionsUtility.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The bundle information service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   General service for moderation-related questions about Entity API.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement system.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translation manager.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory,
    protected CacheBackendInterface $cache,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $bundleInfo,
    protected ModerationInformationInterface $moderationInformation,
    protected Token $token,
    protected TranslationInterface $stringTranslation,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions(EntityInterface $entity): array {
    $transitionStorage = $this->entityTypeManager->getStorage('scheduled_transition');
    $ids = $transitionStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity__target_type', $entity->getEntityTypeId())
      ->condition('entity__target_id', $entity->id())
      ->execute();
    return $transitionStorage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicableBundles(): array {
    $bundles = [];

    $bundleInfo = $this->bundleInfo->getAllBundleInfo();
    foreach ($bundleInfo as $entityTypeId => $entityTypeBundles) {
      $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
      $entityTypeBundles = array_filter(
        $entityTypeBundles,
        fn ($bundleId): bool => $this->moderationInformation->shouldModerateEntitiesOfBundle($entityType, $bundleId),
        \ARRAY_FILTER_USE_KEY,
      );
      $bundles[$entityTypeId] = array_keys($entityTypeBundles);
    }

    return array_filter($bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles(): array {
    $enabledBundlesCache = $this->cache->get(static::CID_SCHEDULED_TRANSITIONS_BUNDLES);
    if ($enabledBundlesCache !== FALSE) {
      return $enabledBundlesCache->data ?? [];
    }

    $enabledBundles = $this->configFactory->get('scheduled_transitions.settings')
      ->get('bundles');
    $enabledBundles = array_map(
      fn (array $bundleConfig) => sprintf('%s:%s', $bundleConfig['entity_type'], $bundleConfig['bundle']),
      is_array($enabledBundles) ? $enabledBundles : []
    );

    $applicableBundles = $this->getApplicableBundles();
    foreach ($applicableBundles as $entityTypeId => &$bundles) {
      $bundles = array_filter(
        $bundles,
        fn (string $bundle) => in_array($entityTypeId . ':' . $bundle, $enabledBundles),
      );
    }

    $applicableBundles = array_filter($applicableBundles);
    $this->cache->set(static::CID_SCHEDULED_TRANSITIONS_BUNDLES, $applicableBundles, Cache::PERMANENT, [SettingsForm::SETTINGS_TAG]);
    return $applicableBundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetRevisionIds(EntityInterface $entity, string $language): array {
    $entityStorage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $entityDefinition = $entityStorage->getEntityType();

    $ids = $entityStorage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition($entityDefinition->getKey('id'), $entity->id())
      ->condition($entityDefinition->getKey('langcode'), $language)
      ->sort($entityDefinition->getKey('revision'), 'DESC')
      ->addTag(static::QUERY_TAG_TARGET_REVISIONS)
      ->execute();

    return array_keys($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function generateRevisionLog(ScheduledTransitionInterface $scheduledTransition, RevisionLogInterface $newRevision): string {
    $entityStorage = $this->entityTypeManager->getStorage($newRevision->getEntityTypeId());
    $latestRevisionId = $entityStorage->getLatestRevisionId($newRevision->id());
    $latest = NULL;
    if ($latestRevisionId) {
      /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $latest */
      $latest = $entityStorage->loadRevision($latestRevisionId);
    }

    $latest ?? throw new ScheduledTransitionMissingEntity('Could not determine latest revision.');

    $options = $scheduledTransition->getOptions();
    if (($options['revision_log_override'] ?? NULL) === TRUE) {
      $template = $options['revision_log'] ?? '';
    }
    else {
      $newIsLatest = $newRevision->getRevisionId() === $latest->getRevisionId();
      $settings = $this->configFactory->get('scheduled_transitions.settings');
      $template = $newIsLatest
        ? $settings->get('message_transition_latest')
        : $settings->get('message_transition_historical');
    }

    $replacements = new ScheduledTransitionsTokenReplacements($scheduledTransition, $newRevision, $latest);
    $replacements->setStringTranslation($this->stringTranslation);
    return $this->tokenReplace($template, $replacements);
  }

  /**
   * Creates a cache tag for scheduled transitions related to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return string
   *   Cache tag to add to lists showing scheduled transitions for an entity.
   */
  public static function createScheduledTransitionsCacheTag(EntityInterface $entity): string {
    return sprintf('scheduled_transitions_for:%s:%s', $entity->getEntityTypeId(), $entity->id());
  }

  /**
   * Replaces all tokens in a given string with appropriate values.
   *
   * @param string $text
   *   A string containing replaceable tokens.
   * @param \Drupal\scheduled_transitions\ScheduledTransitionsTokenReplacements $replacements
   *   A replacements object.
   *
   * @return string
   *   The string with the tokens replaced.
   */
  protected function tokenReplace(string $text, ScheduledTransitionsTokenReplacements $replacements): string {
    $tokenData = ['scheduled-transitions' => $replacements->getReplacements()];
    return $this->token->replace($text, $tokenData);
  }

}
