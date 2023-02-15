<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\localgov_menu_link_group\Entity\LocalGovMenuLinkGroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of localgov_menu_link_group entities.
 */
class LocalGovMenuLinkGroupListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'localgov_menu_link_group_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header = [];
    $header['label'] = $this->t('Menu link group');
    $header['parent'] = $this->t('Parent menu link');
    $header['status'] = $this->t('Enabled');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $group) {

    $row['label'] = $group->label();

    $row['parent'] = [
      '#markup' => $this->determineParentMenuLinkLabel($group),
    ];

    $row['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $group->status(),
      '#disabled' => TRUE,
    ];

    return $row + parent::buildRow($group);
  }

  /**
   * Find the label of the parent menu link for the given group.
   */
  protected function determineParentMenuLinkLabel(LocalGovMenuLinkGroupInterface $group): MarkupInterface {

    $parent_menu_link_id = $group->get('parent_menu_link');

    $is_unknown_menu_link = !$this->menuLinkManager->hasDefinition($parent_menu_link_id);
    if ($is_unknown_menu_link) {
      return new FormattableMarkup('', []);
    }

    $parent_menu_link_definition = $this->menuLinkManager->getDefinition($parent_menu_link_id);
    $parent_menu_link_title = $parent_menu_link_definition['title'] ?? new FormattableMarkup('', []);

    return $parent_menu_link_title;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($entity_type, $storage);

    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * Menu link definition service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

}
