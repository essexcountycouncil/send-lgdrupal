<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Normalizes/denormalizes Drupal config entity objects into an array structure.
 */
class ConfigEntityNormalizer extends NormalizerBase {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['openreferral_json'];

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ConfigEntityInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = static::getDataWithoutInternals($object->toArray());

    if (!empty($context['field'])) {
      [, $field_properties] = explode(':', $context['field']['field_name'], 2);
    }
    if (!empty($field_properties)) {
      $attributes = $attributes[$field_properties];
    }

    return $attributes;
  }

  /**
   * Gets the given data without the internal implementation details.
   *
   * @param array $data
   *   The data that is either currently or about to be stored in configuration.
   *
   * @return array
   *   The same data, but without internals. Currently, that is only the '_core'
   *   key, which is reserved by Drupal core to handle complex edge cases
   *   correctly. Data in the '_core' key is irrelevant to clients reading
   *   configuration, and is not allowed to be set by clients writing
   *   configuration: it is for Drupal core only, and managed by Drupal core.
   *
   * @see https://www.drupal.org/node/2653358
   */
  protected static function getDataWithoutInternals(array $data) {
    return array_diff_key($data, ['_core' => TRUE]);
  }

}
