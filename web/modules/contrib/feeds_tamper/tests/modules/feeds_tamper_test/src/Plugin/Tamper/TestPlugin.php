<?php

namespace Drupal\feeds_tamper_test\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Tamper test plugin for Feeds Tamper tests.
 *
 * @Tamper(
 *   id = "feeds_tamper_test",
 *   label = @Translation("Test"),
 *   description = @Translation("This plugin adds 'test' to the value."),
 *   category = "Text"
 * )
 */
class TestPlugin extends TamperBase {

  const SETTING_TEXT = 'text';
  const SETTING_ENABLED = 'enabled';
  const SETTING_NUMBER = 'number';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_TEXT] = '';
    $config[self::SETTING_ENABLED] = FALSE;
    $config[self::SETTING_NUMBER] = NULL;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_TEXT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#default_value' => $this->getSetting(self::SETTING_TEXT),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(self::SETTING_TEXT)) {
      $form_state->setValue(self::SETTING_ENABLED, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $text = $form_state->getValue(self::SETTING_TEXT);
    $this->setConfiguration([
      self::SETTING_TEXT => $text,
      self::SETTING_ENABLED => $form_state->getValue(self::SETTING_ENABLED),
      self::SETTING_NUMBER => strlen($text),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    return $data . 'test';
  }

}
