<?php

/**
 * @file
 * Hooks provided by the Search API Location module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the available location input plugins.
 *
 * Modules may implement this hook to alter the information that defines
 * location inputs. All properties that are available in
 * Drupal\search_api_location\Annotation\LocationInput can be altered here, with
 * the addition of the "class" and "provider" keys.
 *
 * @param array $infos
 *   The location input info array, keyed by input IDs.
 *
 * @see \Drupal\search_api_location\LocationInput\LocationInputPluginBase
 */
function hook_search_api_location_input_info_alter(array &$infos) {
  $infos['geocode_map']['label'] = t('A complete different description');
}

/**
 * @} End of "addtogroup hooks".
 */
