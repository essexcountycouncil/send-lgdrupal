<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the localGov_menu_link_group config entity add/edit forms.
 */
class LocalGovMenuLinkGroupForm extends EntityForm {

  const ENTITY_ID_PREFIX = 'localgov_menu_link_group_';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $group = $this->entity;
    $parent_menu_name = $group->get('parent_menu');
    $parent_menu_link = $group->get('parent_menu_link');
    $child_menu_links = $group->get('child_menu_links');

    $form = parent::form($form, $form_state);

    $form['group_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group name'),
      '#description' => $this->t('It will act as label of the menu link for this group.'),
      '#description_display' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $group->label(),
      '#required' => TRUE,
    ];

    if ($group->isNew()) {
      $field_prefix = '<span dir="ltr">' . self::ENTITY_ID_PREFIX;
      $field_suffix = '</span>&lrm;';
    }
    else {
      $field_prefix = '';
      $field_suffix = '';
    }

    $form['id'] = [
      '#type'  => 'machine_name',
      '#default_value' => $group->id(),
      '#field_prefix' => $field_prefix,
      '#field_suffix' => $field_suffix,
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['group_label'],
      ],
      '#disabled' => !$group->isNew(),
    ];

    $form['status'] = [
      '#title' => $this->t('Enabled'),
      '#type'  => 'checkbox',
      '#default_value' => $group->status(),
    ];

    $form['weight'] = [
      '#title' => $this->t('Weight of its menu link'),
      '#type'  => 'number',
      '#default_value' => $group->get('weight'),
    ];

    $form['parent_menu'] = [
      '#type'  => 'value',
      '#value' => $parent_menu_name,
    ];

    $parent_menu_link_option = $this->prepareMenuLinkOption($parent_menu_link, $parent_menu_name);
    $form['parent_menu_link'] = $this->menuLinkSelector->parentSelectElement($parent_menu_link_option);
    $form['parent_menu_link']['#title'] = $this->t('Parent menu link');
    $form['parent_menu_link']['#description'] = $this->t('The menu link for this group will appear as a **child** of this menu link.  Example: Add content.');
    $form['parent_menu_link']['#description_display'] = TRUE;
    $form['parent_menu_link']['#required'] = TRUE;

    $form['child_menu_links'] = $this->menuLinkSelector->parentSelectElement('admin:');
    $form['child_menu_links']['#title'] = $this->t('Child menu links');
    $form['child_menu_links']['#description'] = $this->t('These will appear as children of the menu link for this group.  Example: Article, Basic page.');
    $form['child_menu_links']['#description_display'] = TRUE;
    $form['child_menu_links']['#multiple'] = TRUE;
    $default_value = array_map([$this, 'prepareMenuLinkOption'], $child_menu_links, array_fill(0, count($child_menu_links), $parent_menu_name));
    $form['child_menu_links']['#default_value'] = $default_value;
    $form['child_menu_links']['#size'] = 20;

    $has_multiselect_form_element = $this->elementInfo->hasDefinition('multiselect');
    if ($has_multiselect_form_element) {
      $form['child_menu_links']['#type'] = 'multiselect';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $this->massageFormValues($form_state);
  }

  /**
   * Update form values before submission.
   *
   * - Prepend the fixed prefix for new group id values.
   * - Strip out menu names from selected menu links.
   * - Extract parent menu name from the selected parent menu link.
   */
  protected function massageFormValues(FormStateInterface $form_state) {

    $group = $this->entity;

    if ($group->isNew()) {
      $group_id = $form_state->getValue('id');
      $group_id_w_prefix = self::ENTITY_ID_PREFIX . $group_id;
      $form_state->setValue('id', $group_id_w_prefix);
    }

    [$parent_menu_name, $parent_menu_link_wo_menu_name] = self::extractMenuLinkParts($form_state->getValue('parent_menu_link'));
    $form_state->setValue('parent_menu', $parent_menu_name);
    $form_state->setValue('parent_menu_link', $parent_menu_link_wo_menu_name);

    $child_menu_links_wo_menu_name = array_map(function (string $menu_link_value): string {
      [, $menu_link_id] = self::extractMenuLinkParts($menu_link_value);
      return $menu_link_id;
    }, $form_state->getValue('child_menu_links'));
    $form_state->setValue('child_menu_links', $child_menu_links_wo_menu_name);
  }

  /**
   * Extract the menu name and menu link id.
   *
   * The menu link names used by the menu link selection dropdown follows
   * the following format: MENU-NAME:MENU-LINK-ID.
   * Example: admin:admin_toolbar_tools.extra_links:node.add.article.  Here
   * "admin" is the menu name and
   * "admin_toolbar_tools.extra_links:node.add.article" is the menu_link_id.
   *
   * @return array
   *   First item: Menu name; Second item: Menu link id.
   *
   * @see Drupal\Core\Menu\MenuParentFormSelector::parentSelectOptionsTreeWalk()
   */
  public static function extractMenuLinkParts(string $raw_menu_link): array {

    [$menu_name] = explode(':', $raw_menu_link);
    $menu_link = substr_replace($raw_menu_link, '', 0, strlen($menu_name) + 1);

    return [$menu_name, $menu_link];
  }

  /**
   * Prepend the menu name to the menu link id.
   *
   * The option values used in the menu link selection dropdown use this
   * format: MENU_NAME:MENU_LINK_ID.  Example: "admin:dblog.overview" where
   * "admin" is the menu name and "dblog.overview" is the menu link id.
   */
  public static function prepareMenuLinkOption(string $menu_link_id, string $menu_name): string {

    $menu_link_w_menu_name = "{$menu_name}:{$menu_link_id}";
    return $menu_link_w_menu_name;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $group = $this->entity;
    $status = $group->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label LocalGov menu link group created.', [
        '%label' => $group->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label LocalGov menu link group has been updated.', [
        '%label' => $group->label(),
      ]));
    }

    $form_state->setRedirect('entity.localgov_menu_link_group.collection');
  }

  /**
   * Helper function to check entity existence.
   *
   * Checks whether a localgov_menu_link_group configuration entity exists.
   */
  public function exist($id) {

    $group_id = self::ENTITY_ID_PREFIX . $id;

    $entity = $this->entityTypeManager->getStorage('localgov_menu_link_group')->getQuery()
      ->accessCheck(TRUE)
      ->condition('id', $group_id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Prepares a localgov_menu_link_group entity form object.
   */
  public function __construct(MenuParentFormSelectorInterface $menu_link_selector, ElementInfoManagerInterface $element_info) {

    $this->menuLinkSelector = $menu_link_selector;
    $this->elementInfo      = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('menu.parent_form_selector'),
      $container->get('element_info')
    );
  }

  /**
   * Menu link dropdown builder.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuLinkSelector;

  /**
   * Render element info service.
   *
   * @var Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

}
