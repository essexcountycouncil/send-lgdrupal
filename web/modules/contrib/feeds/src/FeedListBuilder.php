<?php

namespace Drupal\feeds;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of feed entities.
 *
 * @see \Drupal\feeds\Entity\Feed
 */
class FeedListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new FeedListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'title' => $this->t('Title'),
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'author' => [
        'data' => $this->t('Author'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => $this->t('Status'),

      'imported' => [
        'data' => $this->t('Imported'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!$entity->access('view') && !$entity->access('update') && !$entity->access('import') && !$entity->access('schedule_import') && !$entity->access('clear') && !$entity->access('unlock')) {
      return [];
    }

    $uri = $entity->toUrl();
    $options = $uri->getOptions();
    $uri->setOptions($options);

    if ($entity->access('view')) {
      $row['title']['data'] = [
        '#type' => 'link',
        '#title' => $entity->label(),
        '#url' => $uri,
      ];
    }
    else {
      $row['title'] = $entity->label();
    }

    $row['type'] = Html::escape($entity->getType()->label());
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['status'] = $entity->isActive() ? $this->t('active') : $this->t('not active');

    $row['imported'] = $this->dateFormatter->format($entity->getImportedTime(), 'short');

    $row['operations']['data'] = $this->buildOperations($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('update')) {
      $operations['edit']['weight'] = 0;
    }

    if ($entity->access('import') && $entity->hasLinkTemplate('import-form')) {
      $operations['import'] = [
        'title' => $this->t('Import'),
        'weight' => 2,
        'url' => $entity->toUrl('import-form'),
      ];
    }

    if ($entity->access('schedule_import') && $entity->hasLinkTemplate('schedule-import-form')) {
      $operations['schedule_import'] = [
        'title' => $this->t('Import in background'),
        'weight' => 3,
        'url' => $entity->toUrl('schedule-import-form'),
      ];
    }

    if ($entity->access('clear') && $entity->hasLinkTemplate('clear-form')) {
      $operations['clear'] = [
        'title' => $this->t('Delete items'),
        'weight' => 4,
        'url' => $entity->toUrl('clear-form'),
      ];
    }

    if ($entity->access('unlock') && $entity->hasLinkTemplate('unlock')) {
      $operations['unlock'] = [
        'title' => $this->t('Unlock'),
        'weight' => 5,
        'url' => $entity->toUrl('unlock'),
      ];
    }

    $destination = $this->redirectDestination->getAsArray();

    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    return $operations;
  }

}
