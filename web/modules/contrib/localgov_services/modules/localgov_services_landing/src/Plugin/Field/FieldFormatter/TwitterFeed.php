<?php

namespace Drupal\localgov_services_landing\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Twitter feed field formatter.
 *
 * @package Drupal\localgov_services_landing\Plugin\Field\FieldFormatter
 *
 * @FieldFormatter(
 *   id = "twitter_feed",
 *   module = "localgov_services_landing",
 *   label = @Translation("Twitter feed"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class TwitterFeed extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => 'Twitter timeline - ' . $item->getUrl()->toString(),
        '#attributes' => [
          'class' => ['twitter-timeline'],
          'href' => $item->getUrl()->toString(),
          'height' => 500,
        ],
        '#attached' => [
          'library' => ['localgov_services_landing/twitter_timeline'],
        ],
      ];
    }

    return $elements;
  }

}
