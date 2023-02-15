<?php

namespace Drupal\office_hours;

use Drupal\Component\Utility\Html;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Factors out OfficeHoursItemList->getItems()->getRows().
 *
 * Note: This is used in 3rd party code since #3219203.
 */
trait OfficeHoursFormatterTrait {

  /**
   * Returns the items of a field.
   *
   * Note: This is used in 3rd party code since #3219203.
   *
   * @param array $values
   *   Result from FieldItemListInterface $items->getValue().
   * @param array $settings
   *   The settings.
   * @param array $field_settings
   *   The field settings.
   * @param array $third_party_settings
   *   The third party settings.
   * @param $time
   *   A time stamp. Defaults to 'REQUEST_TIME'.
   *
   * @return array
   *   The formatted list of slots.
   */
  public function getRows(array $values, array $settings, array $field_settings, array $third_party_settings = [], $time = NULL) {

    $default_office_hours = [
      'startday' => NULL,
      'endday' => NULL,
      'closed' => $this->t(Html::escape($settings['closed_format'])),
      'current' => FALSE,
      'next' => FALSE,
      'slots' => [],
      'formatted_slots' => [],
      'comments' => [],
    ];

    // Initialize $office_hours.
    $office_hours = [];
    // Create 7 empty weekdays, using date_api as key (0=Sun, 6=Sat).
    for ($day = 0; $day < OfficeHoursDateHelper::DAYS_PER_WEEK; $day++) {
      $office_hours[$day] = ['startday' => $day] + $default_office_hours;
    }
    // Move items to $office_hours.
    foreach ($values as $key => $value) {
      $day = $value['day'];
      $office_hours[$day] = $office_hours[$day] ?? ['startday' => $day] + $default_office_hours;
      $office_hours[$day]['slots'][] = [
        'start' => $value['starthours'],
        'end' => $value['endhours'],
        'comment' => $value['comment'] ?? '',
      ];
      if (isset($value['current'])) {
        $office_hours[$day]['current'] = $value['current'];
      }
    }

    $next = $this->nextDay;
    if ($next !== NULL) {
      $office_hours[$next]['next'] = TRUE;
    }

    /*
     * We have a list of all possible rows, marking the next and current day.
     * Now, filter according to formatter settings.
     */

    // Reorder weekdays to match the first day of the week, using formatter settings.
    $office_hours = OfficeHoursDateHelper::weekDaysOrdered($office_hours, $settings['office_hours_first_day']);
    // Compress all slots of the same day into one item.
    if ($settings['compress']) {
      $office_hours = $this->compressSlots($office_hours);
    }
    // Group identical, consecutive days into one item.
    if ($settings['grouped']) {
      $office_hours = $this->groupDays($office_hours);
    }

    // From here, no more adding/removing, only formatting.
    // Format the day names.
    $office_hours = $this->formatLabels($office_hours, $settings, $third_party_settings);
    // Format the start and end time into one slot.
    $office_hours = $this->formatSlots($office_hours, $settings, $field_settings);

    // Return the filtered days/slots/items/rows.
    switch ($settings['show_closed']) {
      case 'all':
        // Nothing to do. All closed days are already added above.
        break;

      case 'open':
        $office_hours = $this->keepOpenDays($office_hours);
        break;

      case 'next':
        $office_hours = $this->keepNextDay($office_hours);
        break;

      case 'none':
        $office_hours = [];
        break;

      case 'current':
        $office_hours = $this->keepCurrentDay($office_hours);
        break;
    }
    return $office_hours;
  }

  /**
   * Formatter: compress the slots: E.g., 0900-1100 + 1300-1700 = 0900-1700.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function compressSlots(array $office_hours) {
    foreach ($office_hours as &$info) {
      if (is_array($info['slots']) && !empty($info['slots'])) {
        // Initialize first slot of the day.
        $compressed_slot = $info['slots'][0];
        // Compress other slot in first slot.
        foreach ($info['slots'] as $index => $slot) {
          $compressed_slot['start'] = min($compressed_slot['start'], $slot['start']);
          $compressed_slot['end'] = max($compressed_slot['end'], $slot['end']);
        }
        $info['slots'] = [0 => $compressed_slot];
      }
    }
    return $office_hours;
  }

  /**
   * Formatter: group days with same slots into 1 line.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function groupDays(array $office_hours) {
    // Keys 0-7 are for sorted Weekdays.
    $previous_key = -100;
    $previous_day = ['slots' => 'dummy'];

    foreach ($office_hours as $key => &$day) {
      // @todo Enable groupDays() for Exception days.
      if (!OfficeHoursDateHelper::isExceptionDay($day)) {
        if ($day['slots'] == $previous_day['slots']) {
          $day['endday'] = $day['startday'];
          $day['startday'] = $previous_day['startday'];
          $day['current'] = $day['current'] || $previous_day['current'];
          $day['next'] = $day['next'] || $previous_day['next'];
          unset($office_hours[(int) $previous_key]);
        }
      }
      $previous_key = (int) $key;
      $previous_day = $day;
    }

    return $office_hours;
  }

  /**
   * Formatter: remove closed days, keeping open days.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepOpenDays(array $office_hours) {
    $result = [];
    foreach ($office_hours as $day => $info) {
      if (!empty($info['slots'])) {
        $result[$day] = $info;
      }
    }
    return $result;
  }

  /**
   * Formatter: remove all days, except the first open day.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepNextDay(array $office_hours) {
    $result = [];
    foreach ($office_hours as $day => $info) {
      if ($info['current'] || $info['next']) {
        $result[$day] = $info;
      }
    }
    return $result;
  }

  /**
   * Formatter: remove all days, except for today.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param int|mixed $time
   *   A time stamp. Defaults to 'REQUEST_TIME'.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepCurrentDay(array $office_hours, $time = NULL) {
    $result = [];

    // Get the current time. May be adapted for User Timezone.
    $time = $this->getRequestTime($time);
    // Convert day number to integer to get '0' for Sunday, not 'false'.
    $today = (int) idate('w', $time); // Get day_number (0=Sun, 6=Sat).

    // Loop through all items.
    // Keep the current one.
    foreach ($office_hours as $info) {
      if ($info['startday'] == $today) {
        $result[$today] = $info;
      }
    }
    return $result;
  }

  /**
   * Formatter: remove all slots, except for current time.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param int|mixed $time
   *   A time stamp. Defaults to 'REQUEST_TIME'.
   *
   * @return array
   *   Reformatted office hours array.
   *
   * @todo Enable isOpen()/keepCurrentSlot() for Exception days.
   */
  protected function keepCurrentSlot(array $office_hours, $time = NULL) {
    $result = [];

    // Get the current time. May be adapted for User Timezone.
    $time = $this->getRequestTime($time);
    // Convert day number to integer to get '0' for Sunday, not 'false'.
    $today = (int) idate('w', $time); // Get day_number (0=Sun, 6=Sat).
    $now = date('Hi', $time); // 'Hi' format, with leading zero (0900).

    // Loop through all items.
    // Detect the current item and the open/closed status.
    foreach ($office_hours as $key => $info) {
      // Calculate start and end times.
      $day = (int) $info['day'];
      // 'Hi' format, with leading zero (0900).
      $start = OfficeHoursDateHelper::format($info['starthours'], 'Hi');
      $end = OfficeHoursDateHelper::format($info['endhours'], 'Hi');

      if ($day == $today - 1 ||
          ($day == $today + 6) ||
          ($day == strtotime('yesterday midnight'))) {
        // We were open yesterday evening, check if we are still open.
        if ($start >= $end && $end > $now) {
          $result[$today] = $info;
        }
      }
      elseif (($day == $today) ||
          ($day == strtotime('today midnight'))) {
        if ($start <= $now) {
          // We were open today, check if we are still open.
          if (($start > $end) // We are open until after midnight.
            || ($start == $end && !is_null($start)) // We are open 24hrs per day.
            || (($start < $end) && ($end > $now)) // We are open, normal time slot.
          ) {
            $result[$today] = $info;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Formatter: format the day name.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param array $settings
   *   User settings array.
   * @param array $third_party_settings
   *   Formatter third party settings array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function formatLabels(array $office_hours, array $settings, array $third_party_settings) {
    $day_format = $settings['day_format'];
    $exceptions_day_format = $settings['exceptions']['date_format'] ?? NULL;
    $group_separator = $settings['separator']['grouped_days'];
    $days_suffix = $settings['separator']['day_hours'];
    foreach ($office_hours as $key => &$info) {
      $is_exception_day = OfficeHoursDateHelper::isExceptionDay($info);
      $pattern = $is_exception_day && $exceptions_day_format ? $exceptions_day_format : $day_format;
      $label = self::formatLabel($pattern, $info, $group_separator);

      $info['label'] = $label ? $label . $days_suffix : '';
    }

    return $office_hours;
  }

  /**
   * Formats the labels of a Render element, like getLabel().
   *
   * @param string $pattern
   *   The day/date formatting pattern.
   * @param array $info
   *   An Office hours value structure.
   * @param string $group_separator
   *   Used if 2 identical days are grouped on one line.
   *
   * @return string
   *   The translated formatted day label.
   */
  public static function formatLabel(string $pattern, array $info, $group_separator) {
    $label = '';

    // Return fast if weekday is not to be display.
    if ($pattern == 'none') {
      return $label;
    }

    // Get the label.
    $label = OfficeHoursDateHelper::getLabel($pattern, $info);
    // Extend the label for Grouped days.
    if (isset($info['endday'])) {
      // Add a dummy 'day', emulating office_hours $value structure.
      $value = ['startday' => $info['endday']] + $info;
      $label
        .= $group_separator
        . OfficeHoursDateHelper::getLabel($pattern, $value);
    }

    return $label;
  }

  /**
   * Formatter: format the office hours list.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param array $settings
   *   User settings array.
   * @param array $field_settings
   *   User field settings array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function formatSlots(array $office_hours, array $settings, array $field_settings) {
    $time_format = OfficeHoursDateHelper::getTimeFormat($settings['time_format']);
    $time_separator = $settings['separator']['hours_hours'];
    $slot_separator = $settings['separator']['more_hours'];
    foreach ($office_hours as &$day_data) {
      $day_data['formatted_slots'] = [];
      $day_data['comments'] = [];
      foreach ($day_data['slots'] as $key => &$slot_data) {
        $formatted_slot = OfficeHoursDateHelper::formatTimeSlot(
          $slot_data['start'],
          $slot_data['end'],
          $time_format,
          $time_separator
        );
        // Store the formatted slot in the slot itself.
        $slot_data['formatted_slot'] = $formatted_slot;
        // Store the arrays of formatted slots & comments in the day.
        $day_data['formatted_slots'][] = $formatted_slot;
        // Always add comment to keep aligned with time slot.
        $day_data['comments'][] = $slot_data['comment'];
      }

      $day_data['formatted_slots'] = empty($day_data['formatted_slots'])
        ? $day_data['closed']
        : implode($slot_separator, $day_data['formatted_slots']);

      // Escape and Translate the comments.
      $day_data['comments'] = array_map('Drupal\Component\Utility\Html::escape', $day_data['comments']);
      if ($field_settings['comment'] == 2) {
        $day_data['comments'] = array_map('t', $day_data['comments']);
      }
      $day_data['comments'] = ($field_settings['comment'])
          ? implode($slot_separator, $day_data['comments'])
          : '';
    }
    return $office_hours;
  }

  /**
   * A helper variable for keepExceptionDaysInHorizon.
   *
   * @var int
   */
  protected static $horizon;

  /**
   * Formatter: remove all Exception days behind horizon.
   *
   * @param int $horizon
   *   The number of days in the future.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Filtered Item list.
   */
  public function keepExceptionDaysInHorizon($horizon) {
    self::$horizon = $horizon;

    $this->filter(function (OfficeHoursItem $item) {
      if (!$item->isExceptionDay()) {
        return TRUE;
      }
      if (self::$horizon == 0) {
        // Exceptions settings are not set / submodule is disabled.
        return FALSE;
      }
      if ($item->isExceptionDayInRange(self::$horizon)) {
        return TRUE;
      }
      return FALSE;
    });
    return $this;
  }

}
