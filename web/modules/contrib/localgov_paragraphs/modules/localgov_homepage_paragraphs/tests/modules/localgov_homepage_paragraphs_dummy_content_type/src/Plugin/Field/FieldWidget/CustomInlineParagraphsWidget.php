<?php

namespace Drupal\localgov_homepage_paragraphs_dummy_content_type\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget;

/**
 * Class custom inline paragraphs widget.
 *
 * @package Drupal\localgov_homepage_paragraphs_dummy_content_type\Plugin\Field\FieldWidget
 *
 * @FieldWidget(
 *   id = "custom_entity_reference_paragraphs",
 *   label = @Translation("Custom Paragraphs Classic"),
 *   description = @Translation("A paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class CustomInlineParagraphsWidget extends InlineParagraphsWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hide_type' => FALSE,
      'description_field' => '_all',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['hide_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide type column'),
      '#default_value' => $this->getSetting('hide_type'),
    ];

    $elements['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#options' => [
        '_all' => $this->t('Default description'),
      ] + $this->fieldNamesOptions(),
      '#default_value' => $this->getSetting('description_field'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Remove type column.
    if ($this->getSetting('hide_type')) {
      unset($element['top']['paragraph_type_title']);
    }

    // Use a single field for the description.
    if ($this->getSetting('description_field') !== '_all') {
      $field_name = $this->getSetting('description_field');
      if (array_key_exists('target_id', $items[$delta]->getValue())) {
        $target = $items[$delta]->getValue()['target_id'];
        $paragraph = Paragraph::load($target);

        if ($paragraph && $paragraph->hasField($field_name) && !$paragraph->get($field_name)->isEmpty()) {
          $element['top']['paragraph_summary']['fields_info']['#markup'] = $paragraph->get($field_name)->first()->getValue()['value'];
        }
      }
    }

    return $element;
  }

  /**
   * All Paragraph fields.
   *
   * Returns an array of all fields attached to paragraphs in an array suitable
   * for options.
   *
   * @return array
   *   Array of fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function fieldNamesOptions() {
    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties(['entity_type' => 'paragraph']);

    $options = [];
    foreach ($fields as $field) {
      $options[$field->getFieldStorageDefinition()->getName()] = $field->label();
    }

    return $options;
  }

}
