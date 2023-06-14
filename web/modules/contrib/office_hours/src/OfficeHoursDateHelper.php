<?php

namespace Drupal\office_hours;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Defines lots of helpful functions for use in massaging dates.
 *
 * For formatting options, see http://www.php.net/manual/en/function.date.php.
 *
 * @todo Centralize here all calls to date().
 * @todo Centralize here all calls to strtotime().
 */
class OfficeHoursDateHelper extends DateHelper {

  /**
   * The number of days per week.
   *
   * @var int
   */
  const DAYS_PER_WEEK = 7;

  /**
   * Defines the format that dates should be stored in.
   *
   * @var \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT;
   */
  const DATE_STORAGE_FORMAT = 'Y-m-d';

  /**
   * Gets the day number of first day of the week.
   *
   * @param string $first_day
   *   First day of week. Optional. If set, this number will be returned.
   *
   * @return int
   *   Returns the first day of the week.
   */
  public static function getFirstDay($first_day = '') {
    if ($first_day === '') {
      $first_day = \Drupal::config('system.date')->get('first_day');
    }
    return $first_day;
  }

  /**
   * Helper function to get the proper format_date() format from the settings.
   *
   * For formatting options, see http://www.php.net/manual/en/function.date.php.
   *
   * @param string $time_format
   *   Time format.
   *
   * @return string
   *   Returns the time format.
   */
  public static function getTimeFormat($time_format) {
    switch ($time_format) {
      case 'G':
        // 24hr without leading zero.
        $time_format = 'G:i';
        break;

      case 'H':
        // 24hr with leading zero.
        $time_format = 'H:i';
        break;

      case 'g':
        // 12hr am/pm without leading zero.
        $time_format = 'g:i a';
        break;

      case 'h':
        // 12hr am/pm with leading zero.
        $time_format = 'h:i a';
        break;
    }
    return $time_format;
  }

  /**
   * Pads date parts with zeros.
   *
   * Helper function for a task that is often required when working with dates.
   * Copied from DateTimePlus::datePad($value, $size) method.
   *
   * @param int $value
   *   The value to pad.
   * @param int $size
   *   (optional) Size expected, usually 2 or 4. Defaults to 2.
   *
   * @return string
   *   The padded value.
   *
   * @deprecated. Replaced with OfficeHoursDateHelper::format($value, $format)
   *
   * @see OfficeHoursDateHelper::format
   */
  public static function datePad($value, $size = 2) {
    return sprintf("%0" . $size . "d", $value);
  }

  /**
   * Helper function to convert a time to a given format.
   *
   * There are too many similar functions:
   *  - OfficeHoursDateHelper::format();
   *  - OfficeHoursDateTime::get() (deprecated);
   *  - OfficeHoursItem, which requires an object;
   *  - OfficeHoursWidgetBase::massageFormValues();
   *
   * For formatting options:
   *
   *   @see https://www.php.net/manual/en/function.date.php
   *   @see https://www.php.net/manual/en/datetime.formats.time.php
   *
   * @todo Use Core/TypedData/ComplexDataInterface.
   *
   * @param string|array $element
   *   A string or array with time element.
   *   Time, in 24hr format '0800', '800', '08:00', '8:00' or empty.
   * @param string $time_format
   *   The requested time format.
   * @param bool $is_end_time
   *   TRUE if the time is an End time of a time slot.
   *
   * @return string
   *   The formatted time, e.g., '08:00'.
   */
  public static function format($element, $time_format, $is_end_time = FALSE) {
    // Be prepared for Datetime and Numeric input.
    // Numeric input set in validateOfficeHoursSlot().
    if (!isset($element)) {
      return NULL;
    }

    // Normalize $element into a 4-digit time.
    if (is_array($element) && array_key_exists('time', $element)) {
      // HTML5 datetime element.
      // Return NULL or time string.
      $time = $element['time'];
    }
    elseif (is_array($element) && array_key_exists('hour', $element)) {
      // SelectList datelist element.
      $time = '';
      if (($element['hour'] !== '') || ($element['minute'] !== '')) {
        if (isset($element['ampm']) && $element['ampm'] === 'pm') {
          $element['hour'] += 12;
        }
        $time = ((int) $element['hour']) * 100 + (int) $element['minute'];
      }
    }
    else {
      // String.
      $time = $element;
    }

    if ($time === NULL || $time === '') {
      return NULL;
    }

    // Normalize time to '09:00' format before creating DateTime object.
    if (!strstr($time, ':')) {
      $time = substr('0000' . $time, -4);
      $hour = substr($time, 0, -2);
      $min = substr($time, -2);
      $time = $hour . ':' . $min;
    }

    $date = new DrupalDateTime($time);
    $formatted_time = $date->format($time_format);
    // Format the 24-hr end time from 0 to 24:00/2400 using a trick.
    if ($is_end_time && $time == '00:00' && !$date->format('G')) {
      $date->setTime(23, 00);
      $formatted_time = str_replace('23', '24', $date->format($time_format), $count);
    }

    return $formatted_time;
  }

  /**
   * Gets the (limited) hours of a day.
   *
   * Mimics DateHelper::hours() function, but that function
   * does not support limiting the hours. The limits are set
   * in the Widget settings form, and used in the Widget form.
   *
   * {@inheritdoc}
   */
  public static function hours($time_format = 'H', $required = FALSE, $start = 0, $end = 23) {
    $hours = [];

    // Get the valid hours. DateHelper API doesn't provide
    // straight method for this.
    $add_midnight = empty($end);
    $start = (empty($start)) ? 0 : max(0, (int) $start);
    $end = (empty($end)) ? 23 : min(23, (int) $end);
    $with_zeroes = in_array($time_format, ['H', 'h']);

    // Begin modified copy from date_hours().
    if (in_array($time_format, ['g', 'h'])) {
      // 12-hour format.
      $min = 1;
      $max = 24;
      for ($i = $min; $i <= $max; $i++) {
        if ((($i >= $start) && ($i <= $end)) || ($end - $start >= 11)) {
          $hour = ($i <= 12) ? $i : $i - 12;
          $hours[$hour] = $hour < 10 && ($with_zeroes) ? "0$hour" : (string) $hour;
        }
      }
      $hours = array_unique($hours);
    }
    else {
      $min = $start;
      $max = $end;
      for ($i = $min; $i <= $max; $i++) {
        $hour = $i;
        $hours[$hour] = $hour < 10 && ($with_zeroes) ? "0$hour" : (string) $hour;
      }
    }
    if ($add_midnight) {
      $hours[00] = $with_zeroes ? "00" : (string) "0";
    }

    $none = ['' => ''];
    $hours = !$required ? $none + $hours : $hours;
    // End modified copy from date_hours().
    return $hours;
  }

  /**
   * Initializes day names, using date_api as key (0=Sun, 6=Sat).
   *
   * Be careful: date_api uses PHP: 0=Sunday and DateObject uses ISO: 1=Sunday.
   *
   * @param string $format
   *   The requested format.
   * @param null|int $day
   *   An option day number.
   *
   * @return array
   *   A list of weekdays in the requested format,
   *   or the requested weekday, if $day is an integer.
   */
  public static function weekDaysByFormat($format, $day = NULL) {
    $days = [];
    switch ($format) {
      case 'number':
        $days = range(1, 7);
        break;

      case 'none':
        $days = array_fill(0, 7, '');
        break;

      case 'long':
        $days = self::weekDays(TRUE);
        break;

      case 'long_untranslated':
        $days = self::weekDaysUntranslated();
        break;

      case 'two_letter':
        // @todo Avoid translation from English to XX, in case of 'Microdata'.
        $days = self::weekDaysAbbr2(TRUE);
        break;

      case 'short':
        // three-letter.
      default:
        $days = self::weekDaysAbbr(TRUE);
        break;
    }
    if ($day !== NULL) {
      // Handle Regular/Seasonal Weekdays: $day 200...206 -> 0..6 .
      $day = OfficeHoursSeason::getWeekday($day);

      return $days[$day];
    }
    return $days;
  }

  /**
   * {@inheritdoc}
   */
  public static function weekDaysOrdered($office_hours, $first_day = '') {
    $new_office_hours = [];

    // Do an initial re-sort on day number for Weekdays and Exception days.
    // Removed. Already done at loading in OfficeHoursItemList::setValue().
    // ksort($office_hours);
    // Fetch first day of week from field settings, if not given already.
    $first_day = OfficeHoursDateHelper::getFirstDay($first_day);

    // Sort Weekdays. Leave Exception days at bottom of list.
    // Copying to new array to preserve keys.
    for ($i = $first_day; $i <= OfficeHoursDateHelper::DAYS_PER_WEEK; $i++) {
      // Rescue the element to be moved.
      if (isset($office_hours[$i])) {
        $new_office_hours[$i] = $office_hours[$i];
        // Remove this week day from the old array.
        unset($office_hours[$i]);
      }
    }

    return $new_office_hours + $office_hours;
  }

  /**
   * Returns the translated label of a Weekday/Exception day, e.g., 'tuesday'.
   *
   * @param string $pattern
   *   The day/date formatting pattern.
   * @param array $value
   *   An Office hours value structure.
   * @param int $day_delta
   *   An optional day_delta.
   *
   * @return bool|string
   *   The formatted day label, e.g., 'tuesday'.
   */
  public static function getLabel(string $pattern, array $value, $day_delta = 0) {
    $label = '';

    if ($day_delta) {
      // This is a following slot.
      $label = t('and');
      return $label;
    }

    $day = $value['day'];
    if ($day === '' || $day === NULL) {
      // A new Exception slot.
      // @todo Value deteriorates in ExceptionsSlot::validate().
      $label = '';
    }
    elseif (OfficeHoursItem::isExceptionDay($value)) {
      if ($pattern == 'l') {
        // Convert date into weekday in widget.
        $label = \Drupal::service('date.formatter')->format($day, 'custom', $pattern);
      }
      else {
        $label = \Drupal::service('date.formatter')->format($day, $pattern);
        // Remove excessive time part.
        $label = str_replace(' - 00:00', '', $label);
      }
    }
    elseif (OfficeHoursSeason::isSeasonHeader($value)) {
      // Handle Season header dates.
      // This is an error. Use $item->getlabel() instead,
      // and make sure the class is OK.
      // @todo Add Watchdog message for Seasonheader::getLabel().
      $label = $value['slots'][0]['comment'];
    }
    else {
      // The day number is a weekday number + Season ID.
      $label = OfficeHoursDateHelper::weekDaysByFormat($pattern, $day);
    }

    return $label;
  }

  /**
   * Creates a date object from an array of date parts.
   *
   * Wrapper function to centralize all Date/Time functions
   * into DateHelper class.
   *
   * @param array $date_parts
   *   Date parts for datetime.
   * @param int|null $timezone
   *   Timezone for datetime.
   * @param array $settings
   *   Optional settings.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A new DateTimePlus object.
   */
  public static function createFromArray(array $date_parts, $timezone = NULL, array $settings = []) {
    return DrupalDateTime::createFromArray($date_parts, $timezone, $settings);
  }

  /**
   * Creates a date object from an input format.
   *
   * Wrapper function to centralize all Date/Time functions into this class.
   *
   * @param string $format
   *   PHP date() type format for parsing the input. This is recommended
   *   to use things like negative years, which php's parser fails on, or
   *   any other specialized input with a known format. If provided the
   *   date will be created using the createFromFormat() method.
   * @param mixed $time
   *   A time.
   * @param mixed $timezone
   *   A timezone.
   * @param array $settings
   *   - validate_format: (optional) Boolean choice to validate the
   *     created date using the input format. The format used in
   *     createFromFormat() allows slightly different values than format().
   *     Using an input format that works in both functions makes it
   *     possible to a validation step to confirm that the date created
   *     from a format string exactly matches the input. This option
   *     indicates the format can be used for validation. Defaults to TRUE.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A new DateTimePlus object.
   *
   * @see http://php.net/manual/datetime.createfromformat.php
   * @see __construct()
   */
  public static function createFromFormat($format, $time, $timezone = NULL, array $settings = []) {
    return DrupalDateTime::createFromFormat($format, $time, $timezone, $settings);
  }

}
