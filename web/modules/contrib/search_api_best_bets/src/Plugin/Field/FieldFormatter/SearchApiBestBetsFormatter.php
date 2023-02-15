<?php

namespace Drupal\search_api_best_bets\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'search_api_best_bets_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "search_api_best_bets_formatter",
 *   module = "search_api_best_bets",
 *   label = @Translation("Search API Best Bets formatter"),
 *   field_types = {
 *     "search_api_best_bets"
 *   }
 * )
 */
class SearchApiBestBetsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'search_api_best_bets_formatter',
        '#query_text' => $item->query_text,
        '#exclude' => $item->exclude,
      ];
    }
    return $element;
  }

}
