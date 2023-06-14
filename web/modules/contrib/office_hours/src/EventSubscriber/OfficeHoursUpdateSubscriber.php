<?php

namespace Drupal\office_hours\EventSubscriber;

use Drupal\office_hours\Event\OfficeHoursEvents;
use Drupal\office_hours\Event\OfficeHoursUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to changes on office_hours field values.
 */
class OfficeHoursUpdateSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[OfficeHoursEvents::OFFICE_HOURS_UPDATE][] = ['hoursUpdateProcess'];
    return $events;
  }

  /**
   * Called whenever the office_hours.hours_update event is dispatched.
   *
   * @param \Drupal\office_hours\Event\OfficeHoursUpdateEvent $event
   *   The event object.
   */
  public function hoursUpdateProcess(OfficeHoursUpdateEvent $event) {
    $values = $event->getValues();
    /*
    // Process of modification values, e.g.,
    $values = array_map(function ($value) {
    if ($value['day'] == '1') {
    $value['starthours'] = '1430';
    return $value;
    }
    return $value;
    }, $values);
     */
    $event->setValues($values);
  }

}
