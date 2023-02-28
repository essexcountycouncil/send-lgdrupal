<?php

namespace Drupal\localgov_openreferral\EventSubscriber;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\localgov_openreferral\Event\GenerateEntityMapping;

/**
 * Event subscriber for suggested map from entity to open referral service.
 */
class GenerateMappingOrganization extends GenerateMappingBase {

  /**
   * Generate suggested mapping to a organization.
   *
   * @param \Drupal\localgov_openreferral\Event\GenerateEntityMapping $event
   *   The Event to process.
   */
  public function generateSuggestions(GenerateEntityMapping $event) {
    if ($event->getPublicType() != 'organization') {
      return;
    }
    $definition = $this->entityTypeManager->getDefinition($event->getEntityTypeId());
    if (!$definition->entityClassImplements(FieldableEntityInterface::class)) {
      return;
    }

    foreach ($this->fieldManager->getFieldDefinitions($event->getEntityTypeId(), $event->getBundle()) as $field) {
      $single_suggestions = [];
      $single_suggestions[] = $this->fieldSuggestionsKnown($field, [
        'body' => 'description',
      ]);
      $single_suggestions[] = $this->fieldSuggestionsType($field, [
        'field_item:email' => 'email',
        'field_item:link' => 'url',
      ]);
      $event->addSuggestionsSingle(array_filter($single_suggestions));
    }
  }

}
