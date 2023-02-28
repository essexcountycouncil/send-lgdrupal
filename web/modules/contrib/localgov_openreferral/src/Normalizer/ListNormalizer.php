<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\serialization\Normalizer\ListNormalizer as SerializerListNormalizer;

/**
 * Converts list objects to arrays.
 *
 * Ordinarily, this would be handled automatically by Serializer, but since
 * there is a TypedDataNormalizer and the Field class extends TypedData, any
 * Field will be handled by that Normalizer instead of being traversed. This
 * class ensures that TypedData classes that also implement ListInterface are
 * traversed instead of simply returning getValue().
 */
class ListNormalizer extends SerializerListNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['openreferral_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $multiple_fields = ['physical_addresses'];
    $properties = parent::normalize($object, $format, $context);
    if (count($properties) == 1 && !in_array($context['field'], $multiple_fields)) {
      return reset($properties);
    }

    return $properties;
  }

}
