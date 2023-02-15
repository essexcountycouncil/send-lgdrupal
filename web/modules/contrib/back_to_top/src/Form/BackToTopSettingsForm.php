<?php

namespace Drupal\back_to_top\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration settings form.
 */
class BackToTopSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'back_to_top_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('back_to_top.settings');
    $settings = $config->get();

    // Include Farbtastic color picker library and other necessary resources.
    $form['#attached']['library'][] = 'core/jquery.farbtastic';
    $form['#attached']['library'][] = 'back_to_top/back_to_top';

    $form['back_to_top_prevent_on_mobile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent on mobile and touch devices'),
      '#description' => $this->t('Do you want to prevent Back To Top on touch devices?'),
      '#default_value' => $settings['back_to_top_prevent_on_mobile'],
    ];
    $form['back_to_top_prevent_in_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent on administration pages and node edit'),
      '#description' => $this->t('Do you want to prevent Back To Top on admin pages?'),
      '#default_value' => $settings['back_to_top_prevent_in_admin'],
    ];
    $form['back_to_top_prevent_in_front'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent on front page'),
      '#description' => $this->t('Do you want to prevent Back To Top on front page?'),
      '#default_value' => $settings['back_to_top_prevent_in_front'],
    ];
    $form['back_to_top_button_trigger'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger'),
      '#description' => $this->t('Set the number of pixel which trigger the Back To Top button default 100'),
      '#default_value' => $settings['back_to_top_button_trigger'],
      '#size' => 10,
      '#maxlength' => 4,
    ];
    $form['back_to_top_button_place'] = [
      '#title' => $this->t('Placement'),
      '#description' => $this->t('Where should the Back To Top button appear?'),
      '#type' => 'select',
      '#options' => [
        1 => $this->t('Bottom right'),
        2 => $this->t('Bottom left'),
        3 => $this->t('Botton center'),
        4 => $this->t('Top right'),
        5 => $this->t('Top left'),
        6 => $this->t('Top center'),
        7 => $this->t('Mid right'),
        8 => $this->t('Mid left'),
        9 => $this->t('Mid center'),
      ],
      '#default_value' => $settings['back_to_top_button_place'],
    ];
    $form['back_to_top_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#description' => $this->t('Set the text of the Back To Top button'),
      '#default_value' => $settings['back_to_top_button_text'],
      '#size' => 30,
      '#maxlength' => 30,
    ];
    $form['back_to_top_button_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want Back To Top to use a PNG-24 image or a Text/Css button?'),
      '#options' => [
        'image' => $this->t('Image (default)'),
        'text' => $this->t('Text/Css'),
      ],
      '#default_value' => $settings['back_to_top_button_type'],
    ];

    // Wrap Text/Css button settings in a fieldset.
    $form['text_button'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Text/Css button settings'),
      '#collapsible' => TRUE,
      '#collapsed' => ($form['back_to_top_button_type']['#default_value'] == 'image' ? TRUE : FALSE),
    ];
    $form['text_button']['back_to_top_bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#description' => $this->t('Button background color default #F7F7F7'),
      '#default_value' => $settings['back_to_top_bg_color'],
      '#size' => 10,
      '#maxlength' => 7,
      '#suffix' => '<div class="color-field" id="back_to_top_bg_color"></div>',
    ];
    $form['text_button']['back_to_top_border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border color'),
      '#description' => $this->t('Border color default #CCCCCC'),
      '#default_value' => $settings['back_to_top_border_color'],
      '#size' => 10,
      '#maxlength' => 7,
      '#suffix' => '<div class="color-field" id="back_to_top_border_color"></div>',
    ];
    $form['text_button']['back_to_top_hover_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hover color'),
      '#description' => $this->t('Hover color default #EEEEEE'),
      '#default_value' => $settings['back_to_top_hover_color'],
      '#size' => 10,
      '#maxlength' => 7,
      '#suffix' => '<div class="color-field" id="back_to_top_hover_color"></div>',
    ];
    $form['text_button']['back_to_top_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text color'),
      '#description' => $this->t('Text color default #333333'),
      '#default_value' => $settings['back_to_top_text_color'],
      '#size' => 10,
      '#maxlength' => 7,
      '#suffix' => '<div class="color-field" id="back_to_top_text_color"></div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('back_to_top.settings');
    $form_values = $form_state->getValues();
    $config->set('back_to_top_prevent_on_mobile', $form_values['back_to_top_prevent_on_mobile'])
      ->set('back_to_top_prevent_in_admin', $form_values['back_to_top_prevent_in_admin'])
      ->set('back_to_top_prevent_in_front', $form_values['back_to_top_prevent_in_front'])
      ->set('back_to_top_button_trigger', $form_values['back_to_top_button_trigger'])
      ->set('back_to_top_button_place', $form_values['back_to_top_button_place'])
      ->set('back_to_top_button_text', $form_values['back_to_top_button_text'])
      ->set('back_to_top_button_type', $form_values['back_to_top_button_type'])
      ->set('back_to_top_bg_color', $form_values['back_to_top_bg_color'])
      ->set('back_to_top_border_color', $form_values['back_to_top_border_color'])
      ->set('back_to_top_hover_color', $form_values['back_to_top_hover_color'])
      ->set('back_to_top_text_color', $form_values['back_to_top_text_color'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
