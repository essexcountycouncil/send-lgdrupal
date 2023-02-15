<?php

namespace Drupal\localgov_openreferral\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\localgov_openreferral\Entity\PropertyMapping;
use Drupal\localgov_openreferral\Entity\PropertyMappingStorage;
use Drupal\localgov_openreferral\Event\GenerateEntityMapping;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base foreEvent subscriber for suggested map from entity.
 */
abstract class GenerateMappingBase implements EventSubscriberInterface {

  /**
   * The Field Mapping service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity Bundle Information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Constructor for GenerateMappingService.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * Implements \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    $events[GenerateEntityMapping::GENERATE][] = ['generateSuggestions'];
    return $events;
  }

  /**
   * Generate suggested mapping to a service.
   *
   * @param \Drupal\localgov_openreferral\Event\GenerateEntityMapping $event
   *   The Event to process.
   */
  abstract public function generateSuggestions(GenerateEntityMapping $event);

  /**
   * Basic Label and ID.
   *
   * Controlled vocabularies that have their ID defined externally are the
   * common exception to using the Drupal UUID for the ID.
   *
   * @param string $entity_type_id
   *   The Entity Type ID.
   *
   * @return array
   *   Keyed for label and UUID.
   */
  protected function suggestionsBasic(string $entity_type_id) {
    $definition = $this->entityTypeManager->getDefinition($entity_type_id);
    return [
      [
        'field_name' => $definition->getKey('label'),
        'public_name' => 'name',
      ],
      [
        'field_name' => $definition->getKey('uuid'),
        'public_name' => 'id',
      ],
    ];
  }

  /**
   * Suggestions for known field names.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field.
   * @param array $known_fields
   *   Field name and mapping property name.
   *
   * @return array
   *   Suggestion if matched.
   */
  protected function fieldSuggestionsKnown(FieldDefinitionInterface $field, array $known_fields) {
    $suggestion = [];
    $field_name = $field->getName();

    if (isset($known_fields[$field_name])) {
      $public_name = $known_fields[$field_name];
      $field_data_definition = $field->getItemDefinition();
      assert($field_data_definition instanceof FieldItemDataDefinitionInterface);
      if (
        // To be flattened, so far this means it's something with its own keys
        // that wants to be in the parent.
        $public_name != '_flatten' &&
        // Otherwise if it has multiple properties we probably want just one.
        (count($field_data_definition->getPropertyDefinitions()) > 1)
        // Of course if it's not defined there needs to be a custom normalizer.
        && ($main_property = $field_data_definition->getMainPropertyName())
      ) {
        $field_name .= ':' . $main_property;
      }
      $suggestion = [
        'field_name' => $field_name,
        'public_name' => $public_name,
      ];
    }

    return $suggestion;
  }

  /**
   * Suggestions for a field type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field.
   * @param array $field_types
   *   Field type and mapping property name.
   *
   * @return array
   *   Suggestion if matched.
   */
  protected function fieldSuggestionsType(FieldDefinitionInterface $field, array $field_types) {
    $suggestion = [];
    $field_name = $field->getName();
    $data_type = $field->getItemDefinition()->getDataType();

    if (isset($field_types[$data_type])) {
      $public_name = $field_types[$data_type];
      $field_data_definition = $field->getItemDefinition();
      assert($field_data_definition instanceof FieldItemDataDefinitionInterface);
      if (
        // To be flattened, so far this means it's something with its own keys
        // that wants to be in the parent.
        $public_name != '_flatten' &&
        // Otherwise if it has multiple properties we probably want just one.
        (count($field_data_definition->getPropertyDefinitions()) > 1)
        // Of course if it's not defined there needs to be a custom normalizer.
        && ($main_property = $field_data_definition->getMainPropertyName())
      ) {
        $field_name .= ':' . $main_property;
      }
      $suggestion = [
        'field_name' => $field_name,
        'public_name' => $public_name,
      ];
    }

    return $suggestion;
  }

  /**
   * Get suggestion for a reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field.
   * @param array $reference_types
   *   Reference field type and mapping property name.
   *
   * @return array
   *   Suggestion if matched.
   */
  protected function fieldSuggestionsReference(FieldDefinitionInterface $field, array $reference_types) {
    $field_name = $field->getName();
    $data_type = $field->getItemDefinition()->getDataType();
    if ($data_type != 'field_item:entity_reference') {
      return [];
    }

    // Sadly the bundle constraint isn't on the typedata destination; and
    // it's peculiar to the settings of a handler, or rather most handlers.
    // There must be a better way of doing this?
    $settings = $field->getSettings();
    if (!empty($settings['target_type'])) {
      if (!empty($settings['handler_settings']) && is_array($settings['handler_settings']['target_bundles'])) {
        $target_bundles = array_keys($settings['handler_settings']['target_bundles']);
      }
      else {
        $target_bundles = array_keys($this->entityBundleInfo->getBundleInfo($settings['target_type']));
      }
      $openreferral_type = NULL;
      $mapping_storage = $this->entityTypeManager->getStorage('localgov_openreferral_mapping');
      assert($mapping_storage instanceof PropertyMappingStorage);
      foreach ($target_bundles as $target_bundle) {
        if ($mapping = $mapping_storage->loadByIds($settings['target_type'], $target_bundle)) {
          assert($mapping instanceof PropertyMapping);
          if (is_null($openreferral_type) || $mapping->getPublicType() === $openreferral_type) {
            $openreferral_type = $mapping->getPublicType();
          }
          else {
            $openreferral_type = FALSE;
          }
        }
      }
      if ($openreferral_type && !empty($reference_types[$openreferral_type])) {
        return [
          'field_name' => $field_name,
          'public_name' => $reference_types[$openreferral_type],
        ];
      }
    }
    return [];
  }

}
