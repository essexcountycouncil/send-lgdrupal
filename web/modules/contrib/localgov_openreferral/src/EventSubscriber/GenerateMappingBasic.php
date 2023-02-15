<?php

namespace Drupal\localgov_openreferral\EventSubscriber;

use Drupal\localgov_openreferral\Event\GenerateEntityMapping;

/**
 * Event subscriber for suggested map from entity to open referral service.
 */
class GenerateMappingBasic extends GenerateMappingBase {

  /**
   * Generate suggested common basic mappings.
   *
   * @param \Drupal\localgov_openreferral\Event\GenerateEntityMapping $event
   *   The Event to process.
   */
  public function generateSuggestions(GenerateEntityMapping $event) {
    $event->addSuggestionsSingle($this->suggestionsBasic($event->getEntityTypeId()));
  }

}
