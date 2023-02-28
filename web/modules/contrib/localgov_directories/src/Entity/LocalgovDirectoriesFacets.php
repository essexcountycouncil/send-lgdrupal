<?php

namespace Drupal\localgov_directories\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\localgov_directories\LocalgovDirectoriesFacetsInterface;
use Drupal\user\UserInterface;

/**
 * Defines the directory facets entity class.
 *
 * @ContentEntityType(
 *   id = "localgov_directories_facets",
 *   label = @Translation("Directory Facets"),
 *   label_collection = @Translation("Directory Facets"),
 *   bundle_label = @Translation("Directory Facets type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\localgov_directories\LocalgovDirectoriesFacetsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\localgov_directories\LocalgovDirectoriesFacetsAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\localgov_directories\Form\LocalgovDirectoriesFacetsForm",
 *       "edit" = "Drupal\localgov_directories\Form\LocalgovDirectoriesFacetsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "localgov_directories_facets",
 *   data_table = "localgov_directories_facets_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer directory facets types",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/directories/facets/add/{localgov_directories_facets_type}",
 *     "add-page" = "/admin/content/directories/facets/add",
 *     "canonical" = "/admin/content/directories/facets/{localgov_directories_facets}",
 *     "edit-form" = "/admin/content/directories/facets/{localgov_directories_facets}/edit",
 *     "delete-form" = "/admin/content/directories/facets/{localgov_directories_facets}/delete",
 *     "collection" = "/admin/content/directories/facets"
 *   },
 *   bundle_entity_type = "localgov_directories_facets_type",
 *   field_ui_base_route = "entity.localgov_directories_facets_type.edit_form"
 * )
 */
class LocalgovDirectoriesFacets extends ContentEntityBase implements LocalgovDirectoriesFacetsInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new directory facets entity is created, set the uid entity reference
   * to the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the directory facets entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the directory facets author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the directory facets was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the directory facets was last edited.'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this Directory facet in relation to other facets.'))
      ->setDefaultValue(0)
      ->setInitialValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
