<?php

namespace Drupal\search_api_exclude_entity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'search_api_exclude_entity' field type.
 *
 * @FieldType(
 *   id = "search_api_exclude_entity",
 *   label = @Translation("Search API Exclude Entity"),
 *   description = @Translation("This field stores search api exclude status."),
 *   default_widget = "search_api_exclude_entity_widget",
 *   default_formatter = "search_api_exclude_entity_formatter"
 * )
 */
class SearchApiExcludeEntityFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(t('Search API Exclude Entity'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];
  }

}
