<?php

namespace Drupal\localgov_events\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Callbacks and handlers for Event node add and edit forms.
 */
class EventsAddEditCallbacks {

  /**
   * Text displayed when using the location from the venue.
   *
   * @var string
   */
  const LOCATION_FROM_VENUE = '<p>Using location from venue. To use a different location remove any existing locations and add a new one below.</p>';

  /**
   * Text displayed when not using the location from the venue.
   *
   * @var string
   */
  const LOCATION_NOT_FROM_VENUE = '<p>Using a different location from venue. To use the same location remove the location below and save the page.</p>';

  /**
   * Return location field widget.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form data.
   *
   * @return array
   *   Location render array to display in form.
   */
  public static function venueChangeCallback(array &$form, FormStateInterface $form_state) {
    $location_element = $form['localgov_event_location'];
    $venue_value = $form_state->getValue('localgov_event_venue');
    if (isset($venue_value[0]['target_id']) && $venue_value[0]['target_id']) {

      // This could be used for automatically loading the location Geo object
      // into the node add/edit form when the venue is changed.
      // For now we just set a message as this is handled on form submit.
      $location_element['#prefix'] = self::LOCATION_FROM_VENUE;
    }

    return $location_element;
  }

  /**
   * Submit handler for the add Event node form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form data.
   */
  public static function submitHandler(array &$form, FormStateInterface $form_state) {
    // Set location if there's a venue but no location.
    $venue_value = $form_state->getValue('localgov_event_venue');
    if (isset($venue_value[0]['target_id'])) {
      $venue = Node::load($venue_value[0]['target_id']);
      $event = $form_state->getFormObject()->getEntity();
      $location_value = $event->get('localgov_event_location')->getValue();
      $venue_location_value = $venue->get('localgov_location')->getValue();
      if (!$location_value && $venue_location_value) {
        $event->set('localgov_event_location', $venue_location_value);
        $event->save();
      }
    }
  }

  /**
   * Configure node add / edit form.
   *
   * @param array $form
   *   Form array.
   */
  public static function configureNodeForm(array &$form) {
    if (isset($form['localgov_event_venue'])) {

      // Add submit and Ajax callbacks.
      $form['actions']['submit']['#submit'][] = 'Drupal\localgov_events\Form\EventsAddEditCallbacks::submitHandler';
      $form['localgov_event_venue']['widget'][0]['target_id']['#ajax'] = [
        'callback' => 'Drupal\localgov_events\Form\EventsAddEditCallbacks::venueChangeCallback',
        'disable-refocus' => FALSE,
        'event' => 'autocompleteclose',
        'wrapper' => 'js-event-location',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ];
      $form['localgov_event_location']['#attributes']['id'][] = 'js-event-location';

      // Add message on how location has been set.
      if (
        isset($form['localgov_event_venue']['widget'][0]['target_id']['#default_value']) &&
        isset($form['localgov_event_location']['widget']['entities'][0]['#entity'])
      ) {
        $venue = $form['localgov_event_venue']['widget'][0]['target_id']['#default_value'];
        $location = $form['localgov_event_location']['widget']['entities'][0]['#entity'];
        $venue_location_id = $venue->get('localgov_location')->getValue()[0]['target_id'];
        if ($location->id() == $venue_location_id) {
          $message = self::LOCATION_FROM_VENUE;
        }
        else {
          $message = self::LOCATION_NOT_FROM_VENUE;
        }
        $form['localgov_event_location']['#prefix'] = $message;
      }
    }
  }

}
