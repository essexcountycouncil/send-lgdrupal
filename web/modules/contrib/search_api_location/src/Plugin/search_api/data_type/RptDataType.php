<?php

namespace Drupal\search_api_location\Plugin\search_api\data_type;

/**
 * Provides the location data type.
 *
 * @SearchApiDataType(
 *   id = "rpt",
 *   label = @Translation("Spatial Recursive Preﬁx Tree"),
 *   description = @Translation("Spatial Recursive Preﬁx Tree data type implementation. Requires lat/lon as input and is needed to enable facet heatmaps")
 * )
 */
class RptDataType extends LocationDataType {

}
