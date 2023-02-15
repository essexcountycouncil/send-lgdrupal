<?php

namespace Drupal\search_api_best_bets\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'search_api_best_bets' field type.
 *
 * @FieldType(
 *   id = "search_api_best_bets",
 *   label = @Translation("Search API Best Bets"),
 *   description = @Translation("This field type stores best bets for Search API."),
 *   default_widget = "search_api_best_bets_widget",
 *   default_formatter = "search_api_best_bets_formatter"
 * )
 */
class SearchApiBestBetsFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'query_text' => [
          'type' => 'varchar',
          'length' => 360,
        ],
        'exclude' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['query_text'] = DataDefinition::create('string')
      ->setLabel(t('Search query text.'))
      ->setRequired(TRUE);

    $properties['exclude'] = DataDefinition::create('boolean')
      ->setLabel(t('Exclude from results'));

    return $properties;
  }

}
