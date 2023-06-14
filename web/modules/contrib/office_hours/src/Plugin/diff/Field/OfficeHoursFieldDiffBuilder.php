<?php

namespace Drupal\office_hours\Plugin\diff\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\FieldDiffBuilderBase;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Plugin to diff office hours fields.
 *
 * @FieldDiffBuilder(
 *   id = "office_hours_diff_builder",
 *   label = @Translation("Office Hours Field Diff"),
 *   field_types = {
 *     "office_hours",
 *   },
 * )
 */
class OfficeHoursFieldDiffBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $items) {
    $result = [];

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $items->filterEmptyItems();
    foreach ($items as $key => $item) {
      $label = $item->getLabel(['day_format' => 'long']);
      $result[$key][] =
        $label
        . ': ' . OfficeHoursDateHelper::format($item->getValue()['starthours'], 'H:i')
        . ' - ' . OfficeHoursDateHelper::format($item->getValue()['endhours'], 'H:i')
        . ' ' . $item->getValue()['comment'];
    }
    return $result;
  }

}
