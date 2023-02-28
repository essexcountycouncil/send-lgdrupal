<?php

namespace Drupal\preview_link\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\preview_link\LinkExpiry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preview link form.
 *
 * @internal
 */
class PreviewLinkForm extends ContentEntityForm {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Calculates link expiry time.
   *
   * @var \Drupal\preview_link\LinkExpiry
   */
  protected $linkExpiry;

  /**
   * PreviewLinkForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\preview_link\LinkExpiry $link_expiry
   *   Calculates link expiry time.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $date_formatter, LinkExpiry $link_expiry, MessengerInterface $messenger) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->dateFormatter = $date_formatter;
    $this->linkExpiry = $link_expiry;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter'),
      $container->get('preview_link.link_expiry'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'preview_link_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /** @var \Drupal\preview_link\PreviewLinkStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('preview_link');
    $related_entity = $this->getRelatedEntity();
    if (!$preview_link = $storage->getPreviewLink($related_entity)) {
      $preview_link = $storage->createPreviewLinkForEntity($related_entity);
    }
    return $preview_link;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $expiration = $this->entity->getGeneratedTimestamp() + $this->linkExpiry->getLifetime() - $this->time->getRequestTime();
    $description = $this->t('Generate a preview link for the <em>@entity_label</em> entity. Preview links will expire @lifetime after they were created.', [
      '@entity_label' => $this->getRelatedEntity()->label(),
      '@lifetime' => $this->dateFormatter->formatInterval($this->linkExpiry->getLifetime(), 1),
    ]);

    $form['preview_link'] = [
      '#theme' => 'preview_link',
      '#title' => $this->t('Preview link'),
      '#description' => $description,
      '#remaining_lifetime' => $this->dateFormatter->formatInterval($expiration),
      '#link' => $this->entity
        ->getUrl()
        ->setAbsolute()
        ->toString(),
    ];

    $form['actions']['submit']['#value'] = $this->t('Regenerate preview link');

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset lifetime'),
      '#submit' => ['::resetLifetime', '::save'],
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * Attempts to load the entity this preview link will be related to.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The content entity interface.
   *
   * @throws \InvalidArgumentException
   *   Only thrown if we cannot detect the related entity.
   */
  protected function getRelatedEntity() {
    $entity = NULL;
    $entity_type_ids = array_keys($this->entityTypeManager->getDefinitions());

    foreach ($entity_type_ids as $entity_type_id) {
      if ($entity = \Drupal::request()->attributes->get($entity_type_id)) {
        break;
      }
    }

    if (!$entity) {
      throw new \InvalidArgumentException('Something went very wrong');
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->regenerateToken(TRUE);
    $this->messenger()->addMessage($this->t('The token has been re-generated.'));
  }

  /**
   * Resets the lifetime of the preview link.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetLifetime(array &$form, FormStateInterface $form_state) {
    $now = $this->time->getRequestTime();
    $this->entity->generated_timestamp = $now;
    $newExpiry = $now + $this->linkExpiry->getLifetime();
    $this->messenger()->addMessage($this->t('Preview link will now expire at %time.', [
      '%time' => $this->dateFormatter->format($newExpiry),
    ]));
  }

}
