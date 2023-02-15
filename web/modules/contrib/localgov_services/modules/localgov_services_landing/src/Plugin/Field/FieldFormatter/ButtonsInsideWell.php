<?php

namespace Drupal\localgov_services_landing\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * CTA button field formatter.
 *
 * @package Drupal\localgov_services_landing\Plugin\Field\FieldFormatter
 *
 * @FieldFormatter(
 *   id = "button_inside_well",
 *   module = "localgov_services_landing",
 *   label = @Translation("Buttons inside well"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class ButtonsInsideWell extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $type = 'cta-info';
      if (isset($item->getValue()['options']['type']) && $item->getValue()['options']['type'] === 'basic') {
        $type = 'cta-action';
      }

      $elements[$delta] = [
        '#theme' => 'button',
        '#title' => $item->getValue()['title'],
        '#url' => $item->getUrl(),
        '#type' => $type,
      ];
    }

    return $elements;
  }

}
