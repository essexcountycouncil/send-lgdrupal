<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\office_hours\Event\OfficeHoursEvents;
use Drupal\office_hours\Event\OfficeHoursUpdateEvent;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursFormatterTrait;

/**
 * Represents an Office hours field.
 */
class OfficeHoursItemList extends FieldItemList implements OfficeHoursItemListInterface {

  use OfficeHoursFormatterTrait {
    getRows as getFieldRows;
  }

  /**
   * An integer representing the next open day.
   *
   * @var int
   */
  protected $nextDay = NULL;

  /**
   * Helper for creating a list item object of several types.
   *
   * {@inheritdoc}
   */
  protected function createItem($offset = 0, $value = NULL) {

    if (!isset($value['day'])) {
      // Empty (added?) Item from List Widget.
      return parent::createItem($offset, $value);
    }

    // Normalize the data in the structure. @todo Needed? Also in getValue().
    $value = OfficeHoursItem::formatValue($value);

    // Use quasi Factory pattern to return Weekday or Exception item.
    if (!OfficeHoursDateHelper::isExceptionDay($value)) {
      // Add Weekday Item.
      return parent::createItem($offset, $value);
    }

    // Add Exception day Item.
    // @todo Move static variables to class level.
    static $pluginManager;
    static $exceptions_list = NULL;
    // First, create a special ItemList with Exception day field definition.
    if (!$exceptions_list) {
      $pluginManager = \Drupal::service('plugin.manager.field.field_type');
      // Get field definition of ExceptionsItem.
      $plugin_id = 'office_hours_exceptions';
      $field_definition = BaseFieldDefinition::create($plugin_id);
      // Create an ItemList with OfficeHoursExceptionsItem items.
      $exceptions_list = OfficeHoursItemList::createInstance($field_definition, $this->name, NULL);
    }
    // Then, add an item to the list with Exception day field definition.
    $item = $pluginManager->createFieldItem($exceptions_list, $offset, $value);

    // Pass item to parent, where it appears amongst Weekdays.
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, $time = NULL) {
    // @todo Move more from getRows here, using itemList, not values.
    $this->getCurrentSlot($time);
    $this->keepExceptionDaysInHorizon($settings['exceptions']['restrict_exceptions_to_num_days'] ?? 0);
    return $this->getFieldRows($this->getValue(), $settings, $field_settings, $third_party_settings, $time);
  }

  /**
   * Returns the timestamp for the current request.
   *
   * @return int
   *   A Unix timestamp.
   *
   * @see \Drupal\Component\Datetime\TimeInterface
   */
  public function getRequestTime($time) {
    $time = ($time) ?? \Drupal::time()->getRequestTime();
    // Call hook. Allows to alter the current time using a timezone.
    $entity = $this->getEntity();
    \Drupal::moduleHandler()->alter('office_hours_current_time', $time, $entity);

    return $time;
  }

  /**
   * Get the current slot and the next day from the Office hours.
   *
   * - Variable $this->nextDay is set to day number.
   * - Attribute 'current' is set on the active slot.
   *
   * @param $time
   *   The desired timestamp.
   */
  protected function getCurrentSlot($time = NULL) {

    if (isset($this->nextDay)) {
      return;
    }

    // Detect the current slot and the open/closed status.
    $time = $this->getRequestTime($time);
    $today = (int) idate('w', $time); // Get day_number: (0=Sun, 6=Sat).
    $now = date('Hi', $time); // 'Hi' format, with leading zero (0900).

    $next_day = NULL;

    $iterator = $this->getIterator();
    while ($iterator->valid()) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      $item = $iterator->current();
      $slot = $item->getValue();
      $day = $slot['day'];

      if ($day <= $today) {
        // Initialize to first day of (next) week, in case we're closed
        // the rest of the week. This works for any first_day.
        if (!isset($first_day_next_week) || ($day < $first_day_next_week)) {
          $first_day_next_week = $day;
        }
      }

      if ($day == $today) {
        $start = $slot['starthours'];
        $end = $slot['endhours'];
        if ($start > $now) {
          // We will open later today.
          $next_day = $day;
        }
        elseif (($start < $end) && ($end < $now)) {
          // We were open today, but are already closed.
        }
        else {
          $next_day = $day;
          // We are still open. @todo move to end of code.
          $slot['current'] = TRUE;
          $iterator->current()->setValue($slot);
        }
      }
      elseif ($day > $today) {
        if ($next_day === NULL) {
          $next_day = $day;
        }
        elseif ($next_day < $today) {
          $next_day = $day;
        }
        else {
          // Just for analysis.
        }
      }
      else {
        // Just for analysis.
      }

      $iterator->next();
    }

    if (!isset($next_day) && isset($first_day_next_week)) {
      $next_day = $first_day_next_week;
    }

    // Set the result: $nextDay
    // Note: also $slot['current'] is set. See above.
    if (isset($next_day)) {
      $this->nextDay = $next_day;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTime(array $settings, array $field_settings, array $third_party_settings) {

    // @see https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age
    // If there are no open days, cache forever.
    if ($this->isEmpty()) {
      return Cache::PERMANENT;
    }

    $date = new DrupalDateTime('now');
    $today = $date->format('w');
    $now = $date->format('Hi');
    $seconds = $date->format('s');

    $next_time = '0000';
    $add_days = 0;

    // Take the 'open/closed' indicator, if set, since it is the lowest.
    $cache_setting = $settings['show_closed'];
    if ($settings['current_status']['position'] !== '') {
      $cache_setting = 'next';
    }
    // Get some settings from field. Do not overwrite defaults.
    // Return the filtered days/slots/items/rows.
    switch ($cache_setting) {
      case 'all':
      case 'open':
      case 'none':
        // These caches never expire, since they are always correct.
        return Cache::PERMANENT;

      case 'current':
        // Cache expires at midnight.
        $next_time = '0000';
        $add_days = 1;
        break;

      case 'next':
        // Get the first (and only) day of the list.
        // Make sure we only receive 1 day, only to calculate the cache.
        $office_hours = $this->getRows($settings, $field_settings, []);
        $next_day = array_shift($office_hours);
        if (!$next_day) {
          return Cache::PERMANENT;
        }

        // Get the difference in hours/minutes between 'now' and next open/closing time.
        foreach ($next_day['slots'] as $slot) {
          $start = $slot['start'];
          $end = $slot['end'];

          if ($next_day['startday'] != $today) {
            // We will open tomorrow or later.
            $next_time = $start;
            $add_days = ($next_day['startday'] - $today + OfficeHoursDateHelper::DAYS_PER_WEEK)
              % OfficeHoursDateHelper::DAYS_PER_WEEK;
            break;
          }
          elseif ($start > $now) {
            // We will open later today.
            $next_time = $start;
            $add_days = 0;
            break;
          }
          elseif (($start > $end) // We are open until after midnight.
            || ($start == $end) // We are open 24hrs per day.
            || (($start < $end) && ($end > $now)) // We are open, normal time slot.
          ) {
            $next_time = $end;
            $add_days = ($start < $end) ? 0 : 1; // Add 1 day if open until after midnight.
            break;
          }
          else {
            // We were open today. Take the first slot of the day.
            if (!isset($first_time_slot_found)) {
              $first_time_slot_found = TRUE;

              $next_time = $start;
              $add_days = OfficeHoursDateHelper::DAYS_PER_WEEK;
            }
            continue; // A new slot might come along.
          }
        }
        break;

      default:
        // We should have covered all options above.
        return Cache::PERMANENT;
    }

    // Set to 0 to avoid php error if time field is not set.
    $next_time = is_numeric($next_time) ? $next_time : '0000';
    // Calculate the remaining cache time.
    $time_left = $add_days * 24 * 3600;
    $time_left += ((int) substr($next_time, 0, 2) - (int) substr($now, 0, 2)) * 3600;
    $time_left += ((int) substr($next_time, 2, 2) - (int) substr($now, 2, 2)) * 60;
    $time_left -= $seconds; // Correct for the current minute.

    return $time_left;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Enable isOpen() for Exception days.
   */
  public function hasExceptionDays() {
    $exception_exists = FALSE;
    // Check if an exception day exists in the table.
    foreach ($this->getValue() as $day => $item) {
      $is_exception_day = OfficeHoursDateHelper::isExceptionDay($item);
      $exception_exists |= $is_exception_day;
    }

    return $exception_exists;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Enable isOpen() for Exception days.
   */
  public function isOpen($time = NULL) {
    $office_hours = $this->keepCurrentSlot($this->getValue(), $time);
    return ($office_hours !== []);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Allow other modules to allow $values.
    if (FALSE) {
      // @todo Disabled until #3063782 is resolved.
      $this->dispatchUpdateEvent(OfficeHoursEvents::OFFICE_HOURS_UPDATE, $value);
    }
    parent::setValue($value, $notify);
  }

  /**
   * Dispatches an event.
   *
   * @param string $event_name
   *   The event to trigger.
   * @param array|null $value
   *   An array of values of the field items, or NULL to unset the field.
   *   Can be changed by EventSubscribers.
   *
   * @return \Drupal\sms\Event\SmsMessageEvent
   *   The dispatched event.
   */
  protected function dispatchUpdateEvent($event_name, &$value) {
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event = new OfficeHoursUpdateEvent($value);
    $event = $event_dispatcher->dispatch($event);
    $value = $event->getValues();
    return $event;
  }

}
