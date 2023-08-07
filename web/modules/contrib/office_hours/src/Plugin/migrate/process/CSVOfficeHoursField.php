<?php

namespace Drupal\office_hours\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Processes a input array of office hours to the correct format for the field.
 *
 * The concat CSVOfficeHours is used to generate a well formed array of
 * opening hours for use in the Office hours field.
 *
 * Available configuration keys:
 * - slots_per_day: (optional) The time slots per day, defaults to 1.
 * - delimiter: (optional) Your time slots should be in the following format:
 *     00:00 - 00:00: Two times separated by a character. With this
 *     option you can set this delimiter, defaults to '-'.
 * - comment: (optional) do we have to import comments for each slot ?
 *     Defaults to FALSE.
 *
 * Examples:
 *
 * Example 1 without comment:
 * @code
 * process:
 *   new_office_hours_field:
 *     plugin: csv_office_hours
 *     slots_per_day: 2
 *     delimiter: '-'
 *     source:
 *        - 'Sunday 1'
 *        - 'Sunday 2'
 *        - 'Monday 1'
 *        - 'Monday 2'
 *        - 'Tuesday 1'
 *        - 'Tuesday 2'
 *        - 'Wednesday 1'
 *        - 'Wednesday 2'
 *        - 'Thursday 1'
 *        - 'Thursday 2'
 *        - 'Friday 1'
 *        - 'Friday 2'
 *        - 'Saturday 1'
 *        - 'Saturday 2'
 * @endcode
 *
 * Example 2 with comment:
 * @code
 * process:
 *   new_office_hours_field:
 *     plugin: csv_office_hours
 *     slots_per_day: 2
 *     delimiter: '-'
 *     comment: true
 *     source:
 *        - 'Sunday 1'
 *        - 'Sunday 1 comment'
 *        - 'Sunday 2'
 *        - 'Sunday 2 comment'
 *        - 'Monday 1'
 *        - 'Monday 1 comment'
 *        - 'Monday 2'
 *        - 'Monday 2 comment'
 *        - 'Tuesday 1'
 *        - 'Tuesday 1 comment'
 *        - 'Tuesday 2'
 *        - 'Tuesday 2 comment'
 *        - 'Wednesday 1'
 *        - 'Wednesday 1 comment'
 *        - 'Wednesday 2'
 *        - 'Wednesday 2 comment'
 *        - 'Thursday 1'
 *        - 'Thursday 1 comment'
 *        - 'Thursday 2'
 *        - 'Thursday 2 comment'
 *        - 'Friday 1'
 *        - 'Friday 1 comment'
 *        - 'Friday 2'
 *        - 'Friday 2 comment'
 *        - 'Saturday 1'
 *        - 'Saturday 1 comment'
 *        - 'Saturday 2'
 *        - 'Saturday 2 comment'
 * @endcode
 *
 * This will import to a field with two time slots set per day.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "csv_office_hours"
 * )
 */
class CSVOfficeHoursField extends OfficeHoursField {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException(sprintf('%s is not an array', var_export($value, TRUE)));
    }

    $slots_per_day = $this->configuration['slots_per_day'] ?? 1;
    $delimiter = $this->configuration['delimiter'] ?? '-';
    $has_comment = $this->configuration['comment'] ?? FALSE;

    if (count($value) !== ($slots_per_day * ($has_comment ? 2 : 1) * OfficeHoursDateHelper::DAYS_PER_WEEK)) {
      throw new MigrateException(sprintf('%s does not have the correct size', var_export($value, TRUE)));
    }

    $office_hours = [];
    for ($i = 0; $i < count($value); $i++) {

      if (!$has_comment || $i % 2 === 0) {
        // Process Hours.
        $time = explode($delimiter, trim($value[$i]));

        $office_hours[] = [
          'day' => floor($i / $slots_per_day / ($has_comment ? 2 : 1)),
          'starthours' => str_replace(':', '', $time[0]),
          'endhours' => str_replace(':', '', $time[1]),
          'comment' => '',
        ];
      }
      // Process Comment.
      else {
        $comment_key = ($i - 1) / 2;
        $value_of_item = &$office_hours[(int) $comment_key];
        $value_of_item = [
          // Convert day number to integer to get '0' for Sunday, not 'false'.
          'day' => (int) $value_of_item['day'],
          // Override empty values because it is not well handled if there
          // is a comment associated with a day. But if there is no comment
          // it has to be empty.
          'starthours' => $value_of_item['starthours'],
          'endhours' => $value_of_item['endhours'],
          // Set default value of comment to empty string.
          'comment' => trim($value[$i]),
        ] + $value_of_item;
      }
    }

    return $office_hours;
  }

}
