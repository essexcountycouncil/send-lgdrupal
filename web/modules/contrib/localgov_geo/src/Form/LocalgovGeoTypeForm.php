<?php

namespace Drupal\localgov_geo\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for geo type forms.
 */
class LocalgovGeoTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add geo type');
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label geo type',
        ['%label' => $entity_type->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Bundle label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => $this->t('The human-readable name of this geo type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\localgov_geo\Entity\LocalgovGeoType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this geo type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['label_token'] = [
      '#title' => $this->t('Default entity label'),
      '#type' => 'textfield',
      '#maxlength' => 1020,
      '#size' => 120,
      '#default_value' => $entity_type->labelToken(),
      '#description' => $this->t('Optional token replacement template to use to generate the label for any entities of this bundle.'),
      '#element_validate' => ['token_element_validate'],
      '#after_build' => ['token_element_validate'],
      '#token_types' => ['localgov_geo'],
    ];

    // Show the token help relevant to this pattern type.
    $form['pattern_container']['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['localgov_geo'],
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save geo type');
    $actions['delete']['#value'] = $this->t('Delete geo type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;

    $entity_type->set('id', trim($entity_type->id()));
    $entity_type->set('label', trim($entity_type->label()));
    $entity_type->set('label_token', $entity_type->labelToken());

    $status = $entity_type->save();

    $t_args = ['%name' => $entity_type->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The geo type %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The geo type %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl($entity_type->toUrl('collection'));
  }

}
