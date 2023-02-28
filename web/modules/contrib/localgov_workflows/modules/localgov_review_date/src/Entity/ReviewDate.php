<?php

namespace Drupal\localgov_review_date\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface;

/**
 * Defines the ReviewDate entity.
 *
 * @ingroup localgov_workflows
 *
 * @ContentEntityType(
 *   id = "review_date",
 *   label = @Translation("Review date"),
 *   base_table = "review_date",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "uid" = "author",
 *   },
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   admin_permission = "administer localgov_review_date",
 * )
 */
class ReviewDate extends ContentEntityBase implements ReviewDateInterface {

  /**
   * Workflow state that transition content to on the next review date.
   */
  public const REVIEW_STATE = 'review';

  /**
   * {@inheritdoc}
   */
  public static function newReviewDate(EntityInterface $entity, string $langcode, ScheduledTransitionInterface $transition, ?AccountInterface $author = NULL, bool $active = TRUE): ReviewDate {
    if (is_null($author)) {
      $author = \Drupal::currentUser();
    }

    $review_date = static::create();
    $review_date
      ->setEntity($entity)
      ->setLangauge($langcode)
      ->setScheduledTransition($transition)
      ->setAuthor($author)
      ->setActive($active)
      ->setCreatedTime(time());

    return $review_date;
  }

  /**
   * {@inheritdoc}
   */
  public static function getActiveReviewDate(EntityInterface $entity, string $langcode): ?ReviewDate {

    if (!$entity->id()) {
      return NULL;
    }

    $review_date_storage = \Drupal::entityTypeManager()->getStorage('review_date');
    $review_date = current($review_date_storage->loadByProperties(
      [
        'entity' => $entity->id(),
        'langcode' => $langcode,
        'active' => TRUE,
      ]
    ));

    if ($review_date instanceof ReviewDate) {
      return $review_date;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return $this->get('active')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('active', $active);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthor(): AccountInterface {
    return $this->get('author')->entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function setAuthor(AccountInterface $author) {
    $this->set('author', $author->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  protected function setCreatedTime($created) {
    $this->set('created', $created);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->get('entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function setEntity(EntityInterface $entity) {
    $this->set('entity', $entity->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage(): string {
    return $this->get('langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  protected function setLangauge(string $langcode) {
    $this->set('langcode', $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReviewTime(): int {
    return $this->get('review')->value;
  }

  /**
   * {@inheritdoc}
   */
  protected function setReviewTime($review) {
    $this->set('review', $review);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheduledTransition(): ?ScheduledTransitionInterface {
    return $this->get('transition')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setScheduledTransition(ScheduledTransitionInterface $transition) {
    $this->set('transition', $transition->id());
    $this->setReviewTime($transition->getTransitionTime());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {

    // There should only be one active review status for each translated entity.
    if ($this->isActive()) {
      $storage = $this->entityTypeManager()->getStorage('review_date');
      $active = $storage->loadByProperties([
        'entity' => $this->getEntity()->id(),
        'langcode' => $this->getLanguage(),
        'active' => TRUE,
      ]);
      foreach ($active as $review_date) {
        if ($review_date->id() !== $this->id()) {
          $review_date->setActive(FALSE);
          $review_date->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity'))
      ->setDescription(t('The entity that has been reviewed.'))
      ->setSetting('target_type', 'node')
      ->setStorageRequired(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setName('langcode')
      ->setLabel(t('Entity language code'))
      ->setDescription(t('The language code fo the review entity.'))
      ->setDefaultValue('x-default')
      ->setStorageRequired(TRUE);

    $fields['transition'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Review scheduled transition'))
      ->setDescription(t('The scheduled transition that will run on review date.'))
      ->setSetting('target_type', 'scheduled_transition');

    $fields['author'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(\t('Author'))
      ->setDescription(\t('The user who created the review date.'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setStorageRequired(TRUE);

    $fields['review'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Review'))
      ->setDescription(t('The time that the entity should be reviewed.'))
      ->setStorageRequired(TRUE);

    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Is this the current active review date for the given entity.'))
      ->setStorageRequired(TRUE);

    return $fields;
  }

}
