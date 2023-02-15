<?php

namespace Drupal\localgov_services\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "link_with_type",
 *   label = @Translation("Link (with type)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkWithType extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['options']['type'] = [
      '#title' => $this->t('Type'),
      '#default_value' => $items[$delta]->options['type'] ?? 'action',
      '#type' => 'select',
      '#options' => [
        'action' => $this->t('Action'),
        'basic' => $this->t('Information'),
      ],
    ];

    return $element;
  }

}
