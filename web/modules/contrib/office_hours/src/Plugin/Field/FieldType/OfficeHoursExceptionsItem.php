<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours_exceptions",
 *   label = @Translation("Office hours exception"),
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 *   no_ui = TRUE,
 * )
 */
class OfficeHoursExceptionsItem extends OfficeHoursItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Add random Exception day value in past and in near future.
    $value = [];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTimeSlot(array $settings) {
    if ($this->day == OfficeHoursItem::EXCEPTION_DAY) {
      // Exceptions header does not have time slot.
      return '';
    }
    return parent::formatTimeSlot($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $settings) {
    $day = $this->day;
    if ($day === '' || $day === NULL) {
      // A new Exception slot.
      // @todo Value deteriorates in ExceptionsSlot::validate().
      $label = '';
    }
    elseif ($day == OfficeHoursItem::EXCEPTION_DAY) {
      $label = $settings['exceptions']['title'] ?? '';
    }
    else {
      $exceptions_day_format = $settings['exceptions']['date_format'] ?? NULL;
      $day_format = $settings['day_format'];
      $days_suffix = $settings['separator']['day_hours'];
      $pattern = $exceptions_day_format ? $exceptions_day_format : $day_format;

      if ($pattern == 'l') {
        // Convert date into weekday in widget.
        $label = \Drupal::service('date.formatter')->format($day, 'custom', $pattern);
      }
      else {
        $label = \Drupal::service('date.formatter')->format($day, $pattern);
        // Remove excessive time part.
        $label = str_replace(' - 00:00', '', $label);
      }
      $label .= $days_suffix;
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function isException() {
    return TRUE;
  }

  /**
   * Returns if a timestamp is in date range of x days to the future.
   *
   * Prerequisite: $item->isException() must be TRUE.
   *
   * @param int $from
   *   The days into the past/future we want to check the timestamp against.
   * @param int $to
   *   The days into the future we want to check the timestamp against.
   *
   * @return bool
   *   TRUE if the timestamp is in range.
   *   TRUE if $rangeInDays has a negative value.
   */
  public function isInRange($from, $to) {
    if ($to <= 0) {
      return TRUE;
    }

    // @todo Allow other values then 0.
    $day = $this->getValue()['day'];
    if ($day < strtotime('today midnight')) {
      return FALSE;
    }

    $maxTime = time() + $to * 24 * 60 * 60;
    if ($day > $maxTime) {
      return FALSE;
    }
    return TRUE;
  }

}
