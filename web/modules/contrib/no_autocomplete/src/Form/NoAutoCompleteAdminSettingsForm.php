<?php

namespace Drupal\no_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to set our options..
 */
class NoAutoCompleteAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'no_autocomplete_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['no_autocomplete.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('no_autocomplete.settings');
    $form['no_autocomplete_login_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use "autocomplete=off" on user login form'),
      '#default_value' => $config->get('no_autocomplete_login_form'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('no_autocomplete.settings')
      ->set('no_autocomplete_login_form', $form_state->getValue('no_autocomplete_login_form'))
      ->save();
  }

}
