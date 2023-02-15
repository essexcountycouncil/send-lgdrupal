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

    $items->filterEmptyItems();
    foreach ($items as $field_key => $field_item) {
      $value = $field_item->getValue();
      $label = OfficeHoursDateHelper::getLabel($pattern = 'long', $value);
      $result[$field_key][] =
        $label
        . ': ' . OfficeHoursDateHelper::format($value['starthours'], 'H:i')
        . ' - ' . OfficeHoursDateHelper::format($value['endhours'], 'H:i')
        . ' ' . $value['comment'];
    }
    return $result;
  }

}
