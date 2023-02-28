<?php

namespace Drupal\localgov_services_navigation;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Alternative value callback for entity reference autocomplete label.
 *
 * Only adds the parent landing page to sublanding pages for services.
 *
 * @see Drupal\Core\Entity\Element\EntityAutocomplete
 * @see locolgov_services_navigation_field_widget_entity_reference_autcomplete_form_alter
 */
class EntityReferenceValue {

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Despite our use case only being singular, just left this as is.
    // Process the #default_value property.
    if ($input === FALSE && isset($element['#default_value']) && $element['#process_default_value']) {
      if (is_array($element['#default_value']) && $element['#tags'] !== TRUE) {
        throw new \InvalidArgumentException('The #default_value property is an array but the form element does not allow multiple values.');
      }
      elseif (!empty($element['#default_value']) && !is_array($element['#default_value'])) {
        // Convert the default value into an array for easier processing in
        // static::getEntityLabels().
        $element['#default_value'] = [$element['#default_value']];
      }

      if ($element['#default_value']) {
        if (!(reset($element['#default_value']) instanceof EntityInterface)) {
          throw new \InvalidArgumentException('The #default_value property has to be an entity object or an array of entity objects.');
        }

        // Extract the labels from the passed-in entity objects, taking access
        // checks into account.
        return static::getEntityLabels($element['#default_value']);
      }
    }

    // Potentially the #value is set directly, so it contains the 'target_id'
    // array structure instead of a string.
    if ($input !== FALSE && is_array($input)) {
      $entity_ids = array_map(function (array $item) {
        return $item['target_id'];
      }, $input);

      $entities = \Drupal::entityTypeManager()->getStorage($element['#target_type'])->loadMultiple($entity_ids);

      return static::getEntityLabels($entities);
    }
  }

  /**
   * Converts an array of entity objects into a string of entity labels.
   *
   * This method is also responsible for checking the 'view label' access on the
   * passed-in entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects.
   *
   * @return string
   *   A string of entity labels separated by commas.
   */
  public static function getEntityLabels(array $entities) {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    $entity_labels = [];
    foreach ($entities as $entity) {
      // Set the entity in the correct language for display.
      $entity = $entity_repository->getTranslationFromContext($entity);

      // Use the special view label, since some entities allow the label to be
      // viewed, even if the entity is not allowed to be viewed.
      $label = ($entity->access('view label')) ? $entity->label() : t('- Restricted access -');

      // Prepend the Landing page name to Sublanding Pages.
      $bundle = $entity->bundle();
      if ($bundle == 'localgov_services_sublanding') {
        $parent_entity = $entity->localgov_services_parent->entity;
        if ($parent_entity) {
          $parent_entity = $entity_repository->getTranslationFromContext($parent_entity);
          $parent_label = ($parent_entity->access('view label')) ? $parent_entity->label() : t('- Restricted access -');
        }
        else {
          $parent_label = t('- Missing Parent Landing page -');
        }
        $label = $parent_label . ' » ' . $label;
      }

      // Not a case that should happen,
      // but left as is into account "autocreated" entities.
      if (!$entity->isNew()) {
        $label .= ' (' . $entity->id() . ')';
      }

      // Labels containing commas or quotes must be wrapped in quotes.
      $entity_labels[] = Tags::encode($label);
    }

    return implode(', ', $entity_labels);
  }

}
