<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\localgov_openreferral\MappingInformation;

/**
 * Normalizes/denormalizes Drupal content entities into an array structure.
 */
class ContentEntityNormalizer extends NormalizerBase {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['openreferral_json'];

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ContentEntityInterface::class;

  /**
   * Mapping information service.
   *
   * @var \Drupal\localgov_openreferral\MappingInformation
   */
  protected $mappingInformation;

  /**
   * Normalizer constructor.
   *
   * @param \Drupal\localgov_openreferral\MappingInformation $mapping_information
   *   Mapping information helper service.
   */
  public function __construct(MappingInformation $mapping_information) {
    $this->mappingInformation = $mapping_information;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $context += [
      'account' => NULL,
    ];
    $parent = (!empty($context['parents']) && count($context['parents'])) ? end($context['parents']) : '__root';
    $openreferral_type = $this->mappingInformation->getPublicType($entity->getEntityTypeId(), $entity->bundle());
    $context['parents'][] = $openreferral_type;

    $object = TypedDataInternalPropertiesHelper::getNonInternalProperties($entity->getTypedData());

    // Called as the entity is referenced in a field.
    // The field configuration is for only one property.
    // eg. `term:uuid`.
    if (!empty($context['field'])) {
      $field_context = explode(':', $context['field']['field_name'], 2);
    }
    if (!empty($field_context[1])) {
      $field_items = $object[$field_context[1]];
      if ($field_items->access('view', $context['account'])) {
        $context['field']['field_name'] = $field_context[1];
        $attributes = $this->serializer->normalize($field_items, $format, $context);
      }
    }
    // Otherwise we iterate over all of the mapped properties for this entity
    // type.
    else {
      $attributes = [];
      $property_mapping = $this->mappingInformation->getPropertyMapping($entity->getEntityTypeId(), $entity->bundle(), $parent);
      foreach ($property_mapping as $property) {
        [$field_name] = explode(':', $property['field_name'], 2);
        $field_items = $object[$field_name];
        if (empty($field_items)) {
          throw new \Exception('Mapped field "' . $field_name . '" not found on object "' . $entity->getEntityTypeId() . '"');
        }
        if ($field_items->access('view', $context['account'])) {
          $context['field'] = $property;
          $normalized_field = $this->serializer->normalize($field_items, $format, $context);
          if ($property['public_name'] == '_flatten') {
            $attributes += $normalized_field;
          }
          elseif (isset($attributes[$property['public_name']]) && is_array($attributes[$property['public_name']])) {
            $attributes[$property['public_name']] = array_merge($attributes[$property['public_name']], $normalized_field);
          }
          else {
            $attributes[$property['public_name']] = $normalized_field;
          }
        }
      }
      // Special case. Taxonomy vocabulary. Unless we add it as a computed or
      // extra field there's no way of mapping defined vocabulary identifiers
      // https://developers.openreferraluk.org/UseOfTaxonomies/#curies-to-use
      if ($openreferral_type == 'taxonomy' && empty($attributes['vocabulary'])) {
        $attributes['vocabulary'] = $this->mappingInformation->getPublicDataType($entity->getEntityTypeId(), $entity->bundle()) ?: $entity->bundle();
      }
    }

    return $attributes;
  }

}
