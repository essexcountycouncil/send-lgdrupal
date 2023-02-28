<?php

namespace Drupal\office_hours;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines lots of helpful functions for use in massaging dates.
 *
 * For formatting options, see http://www.php.net/manual/en/function.date.php.
 */
class OfficeHoursDateHelper extends DateHelper {

  /**
   * The number of days per week.
   *
   * @var int
   */
  const DAYS_PER_WEEK = 7;

  /**
   * The special value for empty hours, since DB holds integers, not NULL.
   *
   * @var int
   */
  const EMPTY_HOURS = -1;

  /**
   * Gets the day number of first day of the week.
   *
   * @return int
   *   Returns the number.
   */
  public static function getFirstDay($first_day = '') {
    if ($first_day == '') {
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
   * @deprecated replaced with OfficeHoursDateHelper::format($value, $format)
   *
   * @see OfficeHoursDateHelper::format($value, $format)
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
   * @param bool $end_time
   *   TRUE if the time is an End time of a time slot.
   *
   * @return string
   *   The formatted time, e.g., '08:00'.
   */
  public static function format($element, $time_format, $end_time = FALSE) {

    // Be prepared for Datetime and Numeric input.
    // Numeric input set in validateOfficeHoursSlot().
    if (!isset($element)) {
      return '';
    }

    // Normalize $element into a 4-digit time.
    if (isset($element['time'])) {
      // HTML5 time element.
      // Return NULL or time string.
      $time = $element['time'];
    }
    elseif (!isset($element['hour'])) {
      $time = $element;
    }
    elseif (($element['hour'] !== '') || ($element['minute'] !== '')) {
      $time = ((int) $element['hour']) * 100 + (int) $element['minute'];
    }
    else {
      $time = '';
    }

    if (!mb_strlen($time)) {
      return NULL;
    }
    // Empty time field (negative value).
    if ($time == OfficeHoursDateHelper::EMPTY_HOURS) {
      return NULL;
    }

    // Normalize time to '9:00' format before creating DateTime object.
    if (!strstr($time, ':')) {
      $time = substr('0000' . $time, -4);
      $hour = substr($time, 0, -2);
      $min = substr($time, -2);
      $time = $hour . ':' . $min;
    }

    $date = new DrupalDateTime($time);
    $formatted_time = $date->format($time_format);
    // Format the 24-hr end time from 0 to 24:00/2400 using a trick.
    if ($end_time && !$date->format('G') &&
       (mb_strlen($time_format) == strspn($time_format, 'GH:i '))) {
      $date = new DrupalDateTime("23:00");
      $formatted_time = str_replace('23', '24', $date->format($time_format), $count);
    }

    return $formatted_time;
  }

  /**
   * Formats a time slot.
   *
   * @param string $start
   *   Start time.
   * @param string $end
   *   End time.
   * @param string $time_format
   *   Time format.
   * @param string $separator
   *   Date separator.
   *
   * @return string
   *   Returns formatted time.
   */
  public static function formatTimeSlot($start, $end, $time_format = 'G:i', $separator = ' - ') {
    $start_time = OfficeHoursDateHelper::format($start, $time_format, FALSE);
    $end_time = OfficeHoursDateHelper::format($end, $time_format, TRUE);

    if (!mb_strlen($start_time) && !mb_strlen($end_time)) {
      // Empty time fields.
      return '';
    }

    $formatted_time = $start_time . $separator . $end_time;
    \Drupal::moduleHandler()->alter('office_hours_time_format', $formatted_time);

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
  public static function hours($format = 'H', $required = FALSE, $start = 0, $end = 23) {
    $hours = [];

    // Get the valid hours. DateHelper API doesn't provide
    // straight method for this.
    $add_midnight = empty($end); // midnight.
    $start = (empty($start)) ? 0 : max(0, (int) $start);
    $end = (empty($end)) ? 23 : min(23, (int) $end);

    // Begin modified copy from date_hours().
    if ($format == 'h' || $format == 'g') {
      // 12-hour format.
      $min = 1;
      $max = 24;
      for ($i = $min; $i <= $max; $i++) {
        if ((($i >= $start) && ($i <= $end)) || ($end - $start >= 11)) {
          $hour = ($i <= 12) ? $i : $i - 12;
          $hours[$hour] = $hour < 10 && ($format == 'H' || $format == 'h') ? "0$hour" : (string) $hour;
        }

      }
      $hours = array_unique($hours);
    }
    else {
      $min = $start;
      $max = $end;
      for ($i = $min; $i <= $max; $i++) {
        $hour = $i;
        $hours[$hour] = $hour < 10 && ($format == 'H' || $format == 'h') ? "0$hour" : (string) $hour;
      }
    }
    if ($add_midnight) {
      $hours[00] = ($format == 'H' || $format == 'h') ? "00" : (string) "0";
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
   *
   * @return array
   *   A list of weekdays in the requested format.
   */
  public static function weekDaysByFormat($format) {
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
    return $days;
  }

  /**
   * {@inheritdoc}
   */
  public static function weekDaysOrdered($office_hours, $first_day = '') {
    $new_office_hours = [];

    // Do an initial re-sort on day number.
    ksort($office_hours);

    // Fetch first day of week from field settings.
    $first_day = OfficeHoursDateHelper::getFirstDay($first_day);
    if ($first_day > 0) {
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

    // Grouped days have 'startday' (in formatter).
    $day = $value['startday'] ?? $value['day'];
    $is_exception_day = OfficeHoursDateHelper::isExceptionDay($value);
    if ($is_exception_day) {
      if ($pattern == 'l') {
        // Workaround for normal case.
        $label = t(date($pattern, $day));
      }
      else {
        $label = \Drupal::service('date.formatter')->format($day, $pattern);
      }
      // Remove excessive time part.
      $label = str_replace(' - 00:00', '', $label);
    }
    elseif ($day === '') {
      // @todo Value deteriorates in ExceptionsSlot::validate().
      $label = '';
    }
    elseif ($pattern) {
      // Function date() cannot convert Weekday number to name.
      $label = OfficeHoursDateHelper::weekDaysByFormat($pattern)[$day];
    }
    else {
      $label = OfficeHoursDateHelper::weekDaysByFormat('long')[$day];
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

  /**
   * Returns whether the day number is a weekday or a date (Exception day).
   *
   * @param array $value
   *   The Office hours data structure, having 'day' as weekday
   *   (using date_api as key (0=Sun, 6=Sat)) or Exception day date.
   *
   * @return bool
   *   False if the day_number is a (sorted) weekday (0 to 7).
   */
  public static function isExceptionDay(array $value) {
    $day = $value['startday'] ?? $value['day']; // Grouped days have 'startday'.
    return (8 <= (int) $day);
  }

}
