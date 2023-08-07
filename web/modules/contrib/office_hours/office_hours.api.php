<?php

/**
 * @file
 * Hooks and API provided by the "Office Hours" module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows to alter the current time.
 *
 * @param int $time
 *   A Unix timestamp.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The Entity to be displayed with Office Hours.
 *
 * @see https://www.drupal.org/project/office_hours/issues/1925272
 */
function hook_office_hours_current_time_alter(int &$time, EntityInterface $entity) {
  // Update the 'current time' to calculate 'current' and 'open' office hours.
  // Assume that all owned entities (for example, offices) should
  // depend on user timezone.
  //
  // Use case is a website as bulletin board.
  // Multiple accounts (clients) with different cities and timezones.
  // A timezone is set in the account's data.
  // The account's articles (offices) have office hours with current status
  // (open\closed).
  // Client sets it to e.g., 8:00-20:00.
  // Visitor has not maintained a timezone.
  // Visitor chooses a city (in a Views' filter)
  // and wants to see if offices in this city are open.
  // Each city has a timezone attached.
  //
  // Adjust content time to content owner's timezone.
  if ($entity instanceof EntityOwnerInterface
    && ($account = $entity->getOwner())
    && ($account->isAuthenticated())
  ) {
    $date_time = new \DateTime();
    $date_time->setTimestamp($time);
    $date_time->setTimezone(new \DateTimeZone($account->getTimeZone()));
    $time = $date_time->getTimestamp();
  }
}

/**
 * Allows to alter the formatted time.
 *
 * This hook contains some re-formatting examples. Pick&Mix!
 *
 * @param string $formatted_time
 *   A formatted time, e.g., '09:00-17:00'.
 */
function hook_office_hours_time_format_alter(string &$formatted_time) {
  // Remove separating space between time and ampm.
  $formatted_time = str_replace([' am', ' pm'], ['am', 'pm'], $formatted_time);
  // Replace 'a.m.' by 'am'.
  $formatted_time = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $formatted_time);
  // Replace '9:00 am' by '9am'. (Separate lines to not destroy '16:00'.)
  $formatted_time = str_replace([':00 a'], [' a'], $formatted_time);
  $formatted_time = str_replace([':00 p'], [' p'], $formatted_time);
  // Convert 'Open all day'.
  // Translation can be managed on /admin/config/regional/translate.
  $formatted_time = str_replace(['12a.m.-12a.m.'], ['Around the clock'], $formatted_time);
  $formatted_time = str_replace(['00:00-24:00'], ['Around the clock'], $formatted_time);
  $formatted_time = str_replace(['0:00-24:00'], ['Around the clock'], $formatted_time);
  $formatted_time = str_replace(['Around the clock'], ['All day open'], $formatted_time);

  // Translate. Translations can be managed using the 'Interface Translation'
  // module on /admin/config/regional/translate.
  $formatted_time = t($formatted_time);
}

/**
 * @} End of "addtogroup hooks".
 */
