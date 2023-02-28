<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait;

/**
 * Converts the Drupal field item object to open referral value.
 */
class GeoFieldItemNormalizer extends FieldItemNormalizer {

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
  protected $supportedInterfaceOrClass = '\Drupal\geofield\Plugin\Field\FieldType\GeofieldItem';

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
   *
   * This normalizer leaves JSON:API normalizer land and enters the land of
   * Drupal core's serialization system. That system was never designed with
   * cacheability in mind, and hence bubbles cacheability out of band. This must
   * catch it, and pass it to the value object that JSON:API uses.
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    assert($field_item instanceof FieldItemInterface);
    $values = [];
    // When other geographic information is needed I guess we use context and
    // fall back to longitude latitude.
    $values['latitude'] = $field_item->get('lat')->getValue();
    $values['longitude'] = $field_item->get('lon')->getValue();
    return $values;
  }

}
