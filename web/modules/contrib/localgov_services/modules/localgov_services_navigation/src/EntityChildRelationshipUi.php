<?php

namespace Drupal\localgov_services_navigation;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeForm;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides helper UI to landing page forms to link to child pages.
 */
class EntityChildRelationshipUi implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * EntityChildRelationshipUi constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(TranslationInterface $translation, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->stringTranslation = $translation;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $fields = [];
    foreach (['localgov_services_landing', 'localgov_services_sublanding'] as $bundle) {
      $fields['node'][$bundle]['form']['localgov_services_navigation_children'] = [
        'label' => $this->t('Unreferenced Children'),
        'description' => $this->t("Pages that link here that have not yet been placed."),
        'weight' => -20,
        'visible' => TRUE,
      ];
    }

    return $fields;
  }

  /**
   * Alters bundle forms to enforce revision handling.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    if (
      $form_object instanceof NodeForm &&
      ($node = $form_object->getEntity()) &&
      in_array($node->bundle(), [
        'localgov_services_landing',
        'localgov_services_sublanding',
      ]) &&
      $node->id()
    ) {
      $form['localgov_services_navigation_children'] = [
        '#items' => $this->childrenField($node),
        '#theme' => 'item_list',
        '#wrapper_attributes' => ['class' => 'localgov-services-children-list'],
        '#attached' => ['library' => 'localgov_services_navigation/children'],
        '#title' => $this->t('Pages linking here'),
      ];
    }
  }

  /**
   * Return 'extra field' with list of non-linked child nodes.
   *
   * @param \Drupal\Node\NodeInterface $node
   *   The `localgov_service_landing` or `localgov_service_sublanding`.
   *
   * @return array
   *   Array of render arrays listing child nodes referencing, but not yet
   *   referenced, by the landing page.
   */
  protected function childrenField(NodeInterface $node) {
    $children_query = $this->entityTypeManager->getStorage('node')->getQuery();
    $children_query->condition('localgov_services_parent', $node->id());
    // Exclude status which automatically get addded to page seperately.
    $children_query->condition('type', 'localgov_services_status', '<>');
    if (!$this->currentUser->hasPermission('bypass node access') && !$this->moduleHandler->hasImplementations('node_grants')) {
      $children_query->condition('status', NodeInterface::PUBLISHED);
    }
    $children = $children_query->accessCheck(TRUE)->execute();

    $unreferenced_children = array_diff($children, self::referencedChildren($node));

    $rows = [];
    foreach ($unreferenced_children as $nid) {
      $child = $this->entityTypeManager->getStorage('node')->load($nid);
      $child = $this->entityRepository->getTranslationFromContext($child);
      assert($child instanceof NodeInterface);
      $row = [
        '#node' => $child,
        '#title' => $child->getTitle(),
        '#type' => $child->type->entity->label(),
        '#url' => $child->toUrl()->toString(),
        '#topics' => [],
        '#id' => $child->id(),
        '#theme' => 'localgov_services_navigation_child',
      ];
      $topics = [];
      if ($child->hasField('localgov_topic_classified')) {
        foreach ($child->localgov_topic_classified as $topic) {
          // Check that the taxonomy term has not been deleted.
          // @see https://github.com/localgovdrupal/localgov_services/issues/157.
          if ($topic->entity instanceof TermInterface) {
            $topics[] = Html::escape($topic->entity->label());
          }
        }
      }
      $row['#topics'] = $topics;
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Get IDs of all nodes that a service landing page links to.
   *
   * Made public as it's prossibly helpful, although it could live in a
   * different class?
   *
   * @param Drupal\Node\NodeInterface $node
   *   The `localgov_service_landing` or `localgov_service_sublanding`.
   */
  public static function referencedChildren(NodeInterface $node) {
    $linked = [];

    // Landing: Both child references, and action links.
    if ($node->bundle() == 'localgov_services_landing') {
      foreach ($node->localgov_destinations as $reference) {
        if (!$reference->isEmpty()) {
          $linked[] = $reference->getValue()['target_id'];
        }
      }
      foreach ($node->localgov_common_tasks as $link) {
        if (
          !$link->isEmpty() &&
          ($url = $link->getUrl()) &&
          $url->isRouted() &&
          $url->getRouteName() == 'entity.node.canonical'
        ) {
          $linked[] = $url->getRouteParameters()['node'];
        }
      }
    }
    // Sublanding: The links in the paragraphs.
    if ($node->bundle() == 'localgov_services_sublanding') {
      foreach ($node->localgov_topics as $paragraphs) {
        if ($paragraphs->isEmpty()) {
          continue;
        }

        // If the paragraph does not have a topic_list_links field then skip.
        // @todo Check for other link fields.
        if (!$paragraphs->entity->hasField('topic_list_links')) {
          continue;
        }

        foreach ($paragraphs->entity->topic_list_links as $link) {
          if (
            !$link->isEmpty() &&
            ($url = $link->getUrl()) &&
            $url->isRouted() &&
            $url->getRouteName() == 'entity.node.canonical'
          ) {
            $linked[] = $url->getRouteParameters()['node'];
          }
        }
      }
    }
    return $linked;
  }

}
