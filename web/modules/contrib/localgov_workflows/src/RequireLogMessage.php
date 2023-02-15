<?php

namespace Drupal\localgov_workflows;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form alter, and setting Base fields, to require a log message.
 */
class RequireLogMessage implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * RequireLogMessage constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Get required status for Entity Bundle Revision Log field.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle_name
   *   Entity bundle machine name.
   *
   * @return bool
   *   TRUE if required.
   *
   * @throws \RuntimeException
   *   If Entity Type is not supported.
   */
  public function isRequired($entity_type_id, $bundle_name) {
    $revision_log_field = $this->getLogField($entity_type_id);
    $revision_log_field_configuration = $revision_log_field->getConfig($bundle_name);
    return $revision_log_field_configuration->isRequired();
  }

  /**
   * Set required status for Entity Bundle Revision Log field.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle_name
   *   Entity bundle machine name.
   * @param bool $status
   *   TRUE if required.
   *
   * @throws \RuntimeException
   *   If Entity Type is not supported.
   */
  public function setRequired($entity_type_id, $bundle_name, $status) {
    $revision_log_field = $this->getLogField($entity_type_id);
    // ::getConfig returns the BaseFieldOverride for the bundle.
    $revision_log_field_configuration = $revision_log_field->getConfig($bundle_name);
    $revision_log_field_configuration->setRequired($status);
    $revision_log_field_configuration->save();
  }

  /**
   * Method to find and return Log Message field for entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   Revisions Log Message field.
   *
   * @throws \RuntimeException
   *   If Entity Type is not supported.
   */
  private function getLogField($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type instanceof ContentEntityTypeInterface) {
      throw new \RuntimeException('Entity type does not support revisions');
    }
    $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

    $revision_log_field_name = $entity_type->getRevisionMetadataKey('revision_log_message');
    return $base_fields[$revision_log_field_name];
  }

  /**
   * Add to Entity Type configuration forms.
   *
   * Assumption is it might be desired to add to other Entity Type forms, but
   * each will be slightly different. Function is written to be as generic as
   * possible, but only apply to a form after testing it for that entity type.
   */

  /**
   * Alter the Node Type configuration form.
   */
  public function alterNodeTypeForm(&$form, FormStateInterface $form_state) {
    $bundle = $form_state->getFormObject()->getEntity();
    if (!$bundle->isNew() && $this->isRequired($bundle->getEntityType()->getBundleOf(), $bundle->id())) {
      $form['workflow']['options']['#default_value']['revision_required'] = 'revision_required';
    }
    $form['workflow']['options']['#options']['revision_required'] = $this->t('Require revision log message');
    $form['workflow']['options']['revision_required']['#states'] = [
      'visible' => [
        'input[name="options[revision]"]' => ['checked' => TRUE],
      ],
    ];
    $form['actions']['submit']['#submit'][] =
      [static::class, 'nodeTypeFormSubmit'];
  }

  /**
   * Submission handler for Node Type Form options.
   */
  public static function nodeTypeFormSubmit($form, FormStateInterface $form_state) {
    $required = (bool) $form_state->getValue(['options', 'revision_required'])
      && (bool) $form_state->getValue(['options', 'revision']);
    $bundle = $form_state->getFormObject()->getEntity();
    self::create(\Drupal::getContainer())
      ->setRequired($bundle->getEntityType()->getBundleOf(), $bundle->id(), $required);
  }

}
