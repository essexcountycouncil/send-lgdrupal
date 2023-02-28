<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours_exceptions",
 *   label = @Translation("Office hours exception day"),
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 * )
 */
class OfficeHoursExceptionsItem extends OfficeHoursItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Add random Exception day value in past and in near future.
    $value = [
      'day' => mt_rand(0, 6),
      'starthours' => mt_rand(00, 23) * 100,
      'endhours' => mt_rand(00, 23) * 100,
      'comment' => mt_rand(0, 1) ? 'additional exception text' : '',
    ];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isExceptionDay() {
    return TRUE;
  }

  /**
   * Returns if a timestamp is in the past.
   *
   * @return bool
   *   TRUE if the timestamp is in the past.
   */
  public function isExceptionDayInPast() {
    $day = $this->getValue()['day'];
    if ($day < strtotime('today midnight')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns if a timestamp is in date range of x days to the future.
   *
   * Prerequisite: $item->isExceptionDay() must be TRUE.
   *
   * @param int $rangeInDays
   *   The days into the future we want to check the timestamp against.
   *
   * @return bool
   *   TRUE if the timestamp is in range.
   *   TRUE if $rangeInDays has a negative value.
   */
  public function isExceptionDayInRange($rangeInDays) {
    if ($rangeInDays <= 0) {
      return TRUE;
    }
    if ($this->isExceptionDayInPast()) {
      return FALSE;
    }
    $day = $this->getValue()['day'];
    $maxTime = time() + $rangeInDays * 24 * 60 * 60;
    if ($day > $maxTime) {
      return FALSE;
    }
    return TRUE;
  }

}
