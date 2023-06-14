<?php

namespace Drupal\search_api_location\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides the location data type.
 *
 * @SearchApiDataType(
 *   id = "location",
 *   label = @Translation("Latitude/Longitude"),
 *   description = @Translation("Location data type implementation")
 * )
 */
class LocationDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    $geom = \geoPHP::load($value);

    if ($geom) {
      $centroid = $geom->getCentroid();
      $lon = $centroid->getX();
      $lat = $centroid->getY();

      return "$lat,$lon";
    }
    else {
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackType() {
    // By returning NULL, we prevent that this data type is handled as a string
    // and e.g. text processors won't run on this value since string is the
    // default fallback type.
    return NULL;
  }

}
