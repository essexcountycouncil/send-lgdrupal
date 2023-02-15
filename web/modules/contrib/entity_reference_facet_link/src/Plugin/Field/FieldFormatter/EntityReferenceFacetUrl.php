<?php

namespace Drupal\entity_reference_facet_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'entity reference facet url' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_facet_url",
 *   label = @Translation("Facet URL"),
 *   description = @Translation("Display the URL of the facet."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceFacetUrl extends EntityReferenceFacetFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function buildElement(Url $url, EntityInterface $entity) {
    return ['#markup' => $url->toString()];
  }

}
