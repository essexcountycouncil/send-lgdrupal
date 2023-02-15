<?php

namespace Drupal\office_hours\Event;

/**
 * Defines events for the office_hours module.
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
final class OfficeHoursEvents {

  /**
   * Name of the event fired when a new incident is reported.
   *
   * This event allows modules to perform an action whenever a new incident is
   * reported via the incident report form. The event listener method receives a
   * \Drupal\events_example\Event\IncidentReportEvent instance.
   *
   * @Event
   *
   * @see \Drupal\office_hours\Event\OfficeHoursUpdateEvent
   *
   * @var string
   */
  const OFFICE_HOURS_UPDATE = 'office_hours.hours_update';

}
