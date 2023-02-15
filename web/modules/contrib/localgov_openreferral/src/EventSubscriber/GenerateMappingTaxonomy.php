<?php

namespace Drupal\localgov_openreferral\EventSubscriber;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\localgov_openreferral\Event\GenerateEntityMapping;

/**
 * Event subscriber for suggested map from entity to open referral taxonomy.
 */
class GenerateMappingTaxonomy extends GenerateMappingBase {

  /**
   * Generate suggested mapping to a taxonomy.
   *
   * @param \Drupal\localgov_openreferral\Event\GenerateEntityMapping $event
   *   The Event to process.
   */
  public function generateSuggestions(GenerateEntityMapping $event) {
    if ($event->getPublicType() != 'taxonomy') {
      return;
    }
    $definition = $this->entityTypeManager->getDefinition($event->getEntityTypeId());
    if (!$definition->entityClassImplements(FieldableEntityInterface::class)) {
      return;
    }

    foreach ($this->fieldManager->getFieldDefinitions($event->getEntityTypeId(), $event->getBundle()) as $name => $field) {
      xdebug_break();
      $single_suggestions = [];
      if ($name == 'parent' && $field->getItemDefinition()->getDataType() == 'field_item:entity_reference') {
        $single_suggestions[] = [
          'field_name' => 'parent:uuid',
          'public_name' => 'parent_id',
        ];
      }
      $event->addSuggestionsSingle(array_filter($single_suggestions));
    }
  }

}
