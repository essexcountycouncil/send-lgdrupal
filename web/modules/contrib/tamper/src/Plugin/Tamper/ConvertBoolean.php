<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for converting text value to boolean value.
 *
 * @Tamper(
 *   id = "convert_boolean",
 *   label = @Translation("Convert to Boolean"),
 *   description = @Translation("Convert to boolean."),
 *   category = "Text"
 * )
 */
class ConvertBoolean extends TamperBase {

  const SETTING_TRUTH_VALUE = 'true_value';
  const SETTING_FALSE_VALUE = 'false_value';
  const SETTING_MATCH_CASE = 'match_case';
  const SETTING_NO_MATCH = 'no_match_value';
  const SETTING_OTHER_TEXT = 'other_text';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();

    $config[self::SETTING_TRUTH_VALUE] = 'true';
    $config[self::SETTING_FALSE_VALUE] = 'false';
    $config[self::SETTING_MATCH_CASE] = FALSE;
    $config[self::SETTING_NO_MATCH] = FALSE;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_TRUTH_VALUE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Truth'),
      '#default_value' => $this->getSetting(self::SETTING_TRUTH_VALUE),
      '#description' => $this->t('The value set to true.'),
    ];

    $form[self::SETTING_FALSE_VALUE] = [
      '#type' => 'textfield',
      '#title' => $this->t('False'),
      '#default_value' => $this->getSetting(self::SETTING_FALSE_VALUE),
      '#description' => $this->t('The value set to false.'),
    ];

    $form[self::SETTING_MATCH_CASE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match case'),
      '#default_value' => $this->getSetting(self::SETTING_MATCH_CASE),
      '#description' => $this->t('Match the case.'),
    ];

    // If no match setting.
    $no_match = $this->getSetting(self::SETTING_NO_MATCH);
    $other_text = '';
    switch (TRUE) {
      case $no_match === TRUE:
        $default = 'true';
        break;

      case $no_match === FALSE:
        $default = 'false';
        break;

      case $no_match === NULL:
        $default = 'null';
        break;

      case $no_match === 'pass':
        $default = 'pass';
        break;

      default:
        $other_text = $no_match;
        $default = 'other';
        break;
    }

    $form[self::SETTING_NO_MATCH] = [
      '#type' => 'radios',
      '#title' => $this->t('If no match'),
      '#default_value' => $default,
      '#options' => [
        'true' => $this->t('True'),
        'false' => $this->t('False'),
        'null' => $this->t('Null'),
        'pass' => $this->t('Do not modify'),
        'other' => $this->t('Other'),
      ],
      '#description' => $this->t('The value to set if the true and false values do not match.'),
    ];

    $form[self::SETTING_OTHER_TEXT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other text'),
      '#default_value' => $other_text,
      '#states' => [
        'visible' => [
          'input[name="plugin_configuration[no_match_value]"]' => ['value' => 'other'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $config = [
      self::SETTING_TRUTH_VALUE => $form_state->getValue(self::SETTING_TRUTH_VALUE),
      self::SETTING_FALSE_VALUE => $form_state->getValue(self::SETTING_FALSE_VALUE),
      self::SETTING_MATCH_CASE => $form_state->getValue(self::SETTING_MATCH_CASE),
      self::SETTING_NO_MATCH => $form_state->getValue(self::SETTING_NO_MATCH),
    ];

    switch ($config[self::SETTING_NO_MATCH]) {
      case 'true':
        $config[self::SETTING_NO_MATCH] = TRUE;
        break;

      case 'false':
        $config[self::SETTING_NO_MATCH] = FALSE;
        break;

      case 'null':
        $config[self::SETTING_NO_MATCH] = NULL;
        break;

      case 'other':
        $config[self::SETTING_NO_MATCH] = $form_state->getValue(self::SETTING_OTHER_TEXT);
        break;
    }

    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    // Copy field value in case 'pass' is set.
    $match_field = $data;
    $truth_value = $this->getSetting(self::SETTING_TRUTH_VALUE);
    $false_value = $this->getSetting(self::SETTING_FALSE_VALUE);

    // Convert match field, truth and false values to lowercase, if no match
    // case required.
    if (!$this->getSetting(self::SETTING_MATCH_CASE)) {
      $match_field = mb_strtolower($match_field);
      $truth_value = mb_strtolower($truth_value);
      $false_value = mb_strtolower($false_value);
    }

    if ($match_field == $truth_value) {
      return TRUE;
    }
    if ($match_field == $false_value) {
      return FALSE;
    }
    if ($this->getSetting(self::SETTING_NO_MATCH) == 'pass') {
      return $data;
    }
    return $this->getSetting(self::SETTING_NO_MATCH);
  }

}
