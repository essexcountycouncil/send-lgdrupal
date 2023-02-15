<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\localgov_openreferral\MappingInformation;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Normalizer class specific for entity reference items in field.
 */
class EntityReferenceFieldNormalizer extends NormalizerBase {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['openreferral_json'];

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = EntityReferenceFieldItemListInterface::class;

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
  public function normalize($field, $format = NULL, array $context = []) {
    // There are two types of references in the standard.
    // One has an intervening 'relationship entity' between the parent and
    // child.
    // This is defined in the standard by the property field. So we hard code it
    // here, if the standard changes, it should be updated.
    $reference_parent = [
      'service_at_locations' => 'service',
      'service_taxonomys' => 'taxonomy',
    ];
    // The other links directly to them, and aren't even multiple.
    $reference_single = [
      'organization' => 'organization',
      'vocabulary' => 'vocabulary',
      'parent_id' => 'parent_id',
    ];

    assert($field instanceof EntityReferenceFieldItemListInterface);
    $attributes = [];

    $parent = $field->getEntity();
    $parent_type = $this->mappingInformation->getPublicType($parent->getEntityTypeId(), $parent->bundle());
    if (!empty($reference_parent[$context['field']['public_name']])) {
      $direction = $reference_parent[$context['field']['public_name']] == $parent_type;

      foreach ($field->referencedEntities() as $entity) {
        $this->addCacheableDependency($context, $entity);
        if (!$entity->access('view')) {
          continue;
        }
        $type = $this->mappingInformation->getPublicType($entity->getEntityTypeId(), $entity->bundle());
        $id = $direction ?
          $parent->uuid() . '-' . $entity->uuid() :
          $entity->uuid() . '-' . $parent->uuid();
        $attribute = ['id' => $id];
        if (count($context['parents']) < 3) {
          $attribute[$type] = $this->serializer->normalize($entity, $format, $context);
        }
        else {
          $attribute[$type]['id'] = $entity->uuid();
        }
        $attributes[] = $attribute;
      }
    }
    elseif (!empty($reference_single[$context['field']['public_name']])) {
      $refrenced_entities = $field->referencedEntities();
      if ($entity = reset($refrenced_entities)) {
        $this->addCacheableDependency($context, $entity);
        if ($entity->access('view')) {
          if (count($context['parents']) < 3) {
            $attributes = $this->serializer->normalize($entity, $format, $context);
          }
          else {
            $attributes = $entity->uuid();
          }
        }
      }
    }
    else {
      foreach ($field->referencedEntities() as $entity) {
        $this->addCacheableDependency($context, $entity);
        if ($entity->access('view')) {
          if (count($context['parents']) < 3) {
            $attributes[] = $this->serializer->normalize($entity, $format, $context);
          }
          else {
            $attributes[] = ['id' => $entity->uuid()];
          }
        }
      }
    }

    return $attributes;
  }

}
