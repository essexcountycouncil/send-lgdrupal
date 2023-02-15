<?php

namespace Drupal\localgov_directories\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for directory facets type forms.
 */
class LocalgovDirectoriesFacetsTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add directory facets type');
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label directory facets type',
        ['%label' => $entity_type->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => $this->t('The human-readable name of this directory facets type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [
          'Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType',
          'load',
        ],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this directory facets type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['weight'] = [
      '#type'          => 'weight',
      '#title'         => $this->t('Weight'),
      '#description'   => $this->t('Facet types are displayed in ascending order by weight.'),
      '#default_value' => $entity_type->get('weight'),
      '#delta'         => 50,
      '#weight'        => 100,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save directory facets type');
    $actions['delete']['#value'] = $this->t('Delete directory facets type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;

    $entity_type->set('id', trim($entity_type->id()));
    $entity_type->set('label', trim($entity_type->label()));
    $entity_type->set('weight', $entity_type->get('weight'));

    $status = $entity_type->save();

    $t_args = ['%name' => $entity_type->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The directory facets type %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The directory facets type %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl($entity_type->toUrl('collection'));
  }

}
