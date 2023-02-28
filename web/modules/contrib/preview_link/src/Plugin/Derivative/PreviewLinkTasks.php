<?php

namespace Drupal\preview_link\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preview link task generation.
 */
class PreviewLinkTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an FieldUiLocalTask object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->basePluginId = $base_plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$this->supportsPreviewLink($entity_type)) {
        continue;
      }

      $this->derivatives["$entity_type_id.generate_preview_link"] = [
        'route_name' => "entity.$entity_type_id.generate_preview_link",
        'title' => $this->t('Preview Link'),
        'base_route' => "entity.$entity_type_id.canonical",
        'weight' => 30,
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

  /**
   * Check if the entity type is supported.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type we're checking.
   *
   * @return bool
   *   TRUE if it supports previews otherwise FALSE.
   */
  protected function supportsPreviewLink(EntityTypeInterface $entityType) {
    return $entityType->isRevisionable();
  }

}
