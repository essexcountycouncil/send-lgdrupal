<?php

namespace Drupal\search_api_exclude_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter;

/**
 * Plugin implementation of the 'search_api_exclude_entity_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "search_api_exclude_entity_formatter",
 *   module = "search_api_exclude_entity",
 *   label = @Translation("Search API Exclude Entity formatter"),
 *   field_types = {
 *     "search_api_exclude_entity"
 *   }
 * )
 */
class SearchApiExcludeEntityFormatter extends BooleanFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['format'] = 'yes-no';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOutputFormats() {
    $formats = parent::getOutputFormats();
    unset($formats['default']);
    return $formats;
  }

}
