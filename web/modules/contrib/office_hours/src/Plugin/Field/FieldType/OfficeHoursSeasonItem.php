<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours_season",
 *   label = @Translation("Office hours in season"),
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 *   no_ui = TRUE,
 * )
 */
class OfficeHoursSeasonItem extends OfficeHoursItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Add random Season ID in past and in near future.
    $value = [];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTimeSlot(array $settings) {
    if (!$this->isSeasonHeader()) {
      return parent::formatTimeSlot($settings);
    }

    // For now, do not show the season dates in the formatter.
    // The user can set them in the Season name, too.
    // This saves many feature requests :-).
    // $format = 'd-m-Y'; // @todo Implement formatting for season header.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $settings) {
    if ($this->isSeasonHeader()) {
      return $this->comment;
    }
    return parent::getLabel($settings);
  }

}
