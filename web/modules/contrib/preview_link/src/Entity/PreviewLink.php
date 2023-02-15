<?php

namespace Drupal\preview_link\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;

/**
 * Defines the node entity class.
 *
 * @ContentEntityType(
 *   id = "preview_link",
 *   label = @Translation("Preview Link"),
 *   base_table = "preview_link",
 *   handlers = {
 *     "storage" = "Drupal\preview_link\PreviewLinkStorage",
 *     "form" = {
 *       "preview_link" = "Drupal\preview_link\Form\PreviewLinkForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "token" = "token",
 *     "entity_id" = "entity_id",
 *     "entity_type_id" = "entity_type_id"
 *   }
 * )
 */
class PreviewLink extends ContentEntityBase implements PreviewLinkInterface {

  /**
   * Keep track on whether we need a new token upon save.
   *
   * @var bool
   */
  protected $needsNewToken = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    $entity_type_id = $this->entity_type_id->value;
    return Url::fromRoute("entity.$entity_type_id.preview_link", [
      $entity_type_id => $this->entity_id->value,
      'preview_token' => $this->getToken(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->get('token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setToken($token) {
    $this->set('token', $token);
    // Add a second so our testing always works.
    $this->set('generated_timestamp', time() + 1);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function regenerateToken($needs_new_token = FALSE) {
    $current_value = $this->needsNewToken;
    $this->needsNewToken = $needs_new_token;
    return $current_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneratedTimestamp() {
    return $this->get('generated_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Preview Token'))
      ->setDescription(t('A token that allows any user to view a preview of this entity.'))
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Id'))
      ->setDescription(t('The entity Id'))
      ->setRequired(TRUE);

    $fields['entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type Id'))
      ->setDescription(t('The entity type Id'))
      ->setRequired(TRUE);

    $fields['generated_timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Generated Timestamp'))
      ->setDescription(t('The time the link was generated'))
      ->setRequired(TRUE);

    return $fields;
  }

}
