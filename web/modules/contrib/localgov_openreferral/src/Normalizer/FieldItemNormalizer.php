<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\serialization\Normalizer\NormalizerBase;
use Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait;

/**
 * Converts the Drupal field item object to open referral value.
 */
class FieldItemNormalizer extends NormalizerBase {

  use SerializedColumnNormalizerTrait;

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['openreferral_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FieldItemInterface::class;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FieldItemNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    assert($field_item instanceof FieldItemInterface);
    /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
    $values = [];
    if (!empty($field_item->getProperties(TRUE))) {
      // We normalize each individual value, so each can do their own casting,
      // if needed.
      $field_properties = TypedDataInternalPropertiesHelper::getNonInternalProperties($field_item);
      if (!empty($context['field'])) {
        $context_property = explode(':', $context['field']['field_name'], 3);
        if (!empty($context_property[1])) {
          $field_properties = [$context_property[1] => $field_properties[$context_property[1]]];
        }
      }
      foreach ($field_properties as $property_name => $property) {
        $values[$property_name] = $this->serializer->normalize($property, $format, $context);
      }
      // Flatten if there is only a single property to normalize.
      $flatten = count($field_properties) === 1 && $field_item::mainPropertyName() !== NULL;
      $values = $flatten ? reset($values) : $values;
    }
    else {
      $values = $field_item->getValue();
    }
    return $values;
  }

}
