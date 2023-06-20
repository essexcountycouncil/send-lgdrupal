<?php

namespace Drupal\office_hours;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Defines some functions for use in caching.
 *
 * Unfortunately, max-age does not work for anonymous users
 * and the Drupal core Page Cache module.
 * For example, see these issues:
 * https://www.drupal.org/docs/drupal-apis/cache-api/cache-tags
 * https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age
 * https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age#s-limitations-of-max-age
 * https://www.drupal.org/docs/8/api/responses/cacheableresponseinterface
 * https://www.drupal.org/project/drupal/issues/2835068
 * https://www.drupal.org/project/drupal/issues/3304772
 * https://www.drupal.org/project/cache_control_override/issues/2962699
 * https://www.drupal.org/project/custom_cache
 * That is why a hook_cron is implemented.
 */
class OfficeHoursCacheHelper implements CacheableDependencyInterface {

  /**
   * Indicates that the item should never be removed unless explicitly deleted.
   *
   * Can have the following values:
   * 0: do nothing;
   * 1: invalidate all Entities with a status formatter (with generic tag);
   * 2: invalidateTagsUsingRenderCache() using entity-specific tag;
   * 3: invalidateTagsUsingStateService();
   */
  const CACHE_INVALIDATION_MODE = 0;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Office Hours formatter settings.
   *
   * @var array
   */
  protected $formatterSettings = [];

  /**
   * An Office Hours ItemList.
   *
   * @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface
   */
  protected $items = NULL;

  /**
   * An array of formatted office_hours, according to formatter.
   *
   * @var array
   */
  protected $officeHours = [];

  /**
   * Constructs a CacheHelper object.
   */
  public function __construct(array $formatter_settings, OfficeHoursItemListInterface $items, array $office_hours) {
    $this->formatterSettings = $formatter_settings;
    $this->items = $items;
    $this->officeHours = $office_hours;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Do not set caching for anonymous users.
    if (\Drupal::currentUser()->isAnonymous()) {
    // return ['session'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $entity = $this->items->getEntity();
    if (!$entity) {
      return [];
    }
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    // Add a tag for the Entity itself,
    // and a tag for the hook_cron invalidation for anonymous users.
    switch (self::CACHE_INVALIDATION_MODE) {
      case 1:
        return [
          "$entity_type:$entity_id",
          "office_hours:field.status",
        ];

      case 2:
        return [
          "$entity_type:$entity_id",
          "office_hours:field.status:$entity_type:$entity_id",
        ];

      default:
        return [
          "$entity_type:$entity_id",
          "office_hours:field.status",
        ];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age
   */
  public function getCacheMaxAge() {
    // Do not set caching for anonymous users.
    if (\Drupal::currentUser()->isAnonymous()) {
      return 0;
    }

    // @todo Add CacheMaxAge when entity has Exception days in/out of horizon.
    // If there are no open days, cache forever.
    if ($this->items->isEmpty()) {
      return Cache::PERMANENT;
    }

    $date = new DrupalDateTime('now');
    $today = $date->format('w');
    $now = $date->format('Hi');
    $seconds = $date->format('s');
    $next_time = '0000';
    $add_days = 0;

    $formatter_settings = $this->formatterSettings;
    $cache_setting = $formatter_settings['show_closed'];
    switch ($cache_setting) {
      case 'all':
      case 'open':
      case 'none':
        // These caches never expire, since they are always correct.
        return Cache::PERMANENT;

      case 'current':
        // Cache expires at midnight. (Is this timezone proof?)
        /*
        $next_time = '0000';
        $add_days = 1;
        break;
         */
        return strtotime('tomorrow midnight') - strtotime('now');

      case 'next':
        // Get the first (and only) day of the list.
        // Make sure we only receive 1 day, only to calculate the cache.
        $office_hours = $this->officeHours;
        $next_day = array_shift($office_hours);
        if (!$next_day) {
          return Cache::PERMANENT;
        }

        // Get the difference in hours/minutes
        // between 'now' and next open/closing time.
        $first_time_slot_found = FALSE;
        foreach ($next_day['slots'] as $slot) {
          $start = $slot['start'];
          $end = $slot['end'];

          if ($next_day['day'] != $today) {
            // We will open tomorrow or later.
            $next_time = $start;
            $seven = OfficeHoursDateHelper::DAYS_PER_WEEK;
            $add_days = ($next_day['day'] - $today + $seven) % $seven;
            break;
          }
          elseif ($start > $now) {
            // We will open later today.
            $next_time = $start;
            $add_days = 0;
            break;
          }
          elseif (($start > $end)
            // We are open until after midnight.
            || ($start == $end)
            // We are open 24hrs per day.
            || (($start < $end) && ($end > $now))
            // We are open, normal times.
          ) {
            $next_time = $end;
            // Add 1 day if open until after midnight.
            $add_days = ($start < $end) ? 0 : 1;
            break;
          }
          else {
            // We were open today. Take the first slot of the day.
            if (!$first_time_slot_found) {
              $first_time_slot_found = TRUE;
              $next_time = $start;
              $add_days = OfficeHoursDateHelper::DAYS_PER_WEEK;
            }
            // Do not stop, but continue. A new slot might come along.
            continue;
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
    // Correct for the current minute.
    $time_left -= $seconds;

    return $time_left;
  }

}
