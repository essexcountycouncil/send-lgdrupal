<?php

namespace Drupal\localgov_directories\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\node\Entity\Node;

/**
 * Display available facet options by selected channel.
 *
 * Grouping by entity reference by bundle would also be solved by
 * https://www.drupal.org/project/drupal/issues/2269823
 *
 * @FieldWidget(
 *   id = "localgov_directories_facet_checkbox",
 *   module = "localgov_directories",
 *   label = @Translation("Directory entry facets"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class FacetFieldWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    // Trying to imagine the best way round this.
    //
    // EntityReferenceItem::getSettableOptions() called by ::getOptions()
    // removes the bundle from the array if there is only one in the available
    // results. At the moment in our case that would be if there is only one
    // bundle, or if there are more, but one has accesible values.
    //
    // I'm lacking imagination at the moment. So this ugly blunt instrument puts
    // it back for now.
    $raw_options = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldDefinition, $items->getEntity())->getReferenceableEntities();
    if (count($raw_options) == 1) {
      $target_type = $this->fieldDefinition->getSetting('target_type');
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);
      $bundle = key($raw_options);
      $bundle_label = (string) $bundles[$bundle]['label'];
      $options = [
        $bundle_label => $options,
      ];
    }

    $enabled = [];
    if ($user_input = $form_state->getValue('localgov_directory_channels')) {
      foreach ($user_input as $user_input_nid) {
        if ($user_input_nid['target_id'] && ($channel = Node::load($user_input_nid['target_id']))) {
          foreach ($channel->localgov_directory_facets_enable as $facet_item) {
            $facet = $facet_item->entity;
            assert($facet instanceof LocalgovDirectoriesFacetsType);
            $enabled[$facet->label()] = $facet->label();
          }
        }
      }
    }
    else {
      foreach ($items->getEntity()->localgov_directory_channels as $channel) {
        if ($node = $channel->entity) {
          foreach ($node->localgov_directory_facets_enable as $facet_item) {
            if ($facet = $facet_item->entity) {
              assert($facet instanceof LocalgovDirectoriesFacetsType);
              $enabled[$facet->label()] = $facet->label();
            }
          }
        }
      }
    }

    // And only allow bundles associated with the channels.
    $options = array_intersect_key($options, $enabled);

    // Set selected from any existing values.
    $selected = $this->getSelectedOptions($items);
    // If there is only one option and it's required default it.
    if ($this->required && count($options) == 1) {
      $single_bundle_options = reset($options);
      if (count($single_bundle_options) == 1) {
        $selected = [key($single_bundle_options)];
      }
    }

    $element += [
      '#type' => 'fieldset',
    ];

    if (empty($options)) {
      $element['#description'] = $this->t('Select directory channels to add facets');
    }
    foreach ($options as $bundle_label => $bundle_options) {
      $element[$bundle_label] = [
        '#title' => $bundle_label,
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $bundle_options,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // Flatten the array again.
    $values = $form_state->getValue($element['#field_name']);
    if ($values) {
      $element['#value'] = [];
      foreach ($values as $options) {
        foreach ($options as $key => $value) {
          if ($value) {
            $element['#value'][$key] = $value;
          }
        }
      }
    }
    // None option.
    if (empty($element['#value'])) {
      $element['#value'] = '_none';
    }

    parent::validateElement($element, $form_state);
  }

}
