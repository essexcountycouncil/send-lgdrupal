<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Str Pos plugin.
 *
 * @Tamper(
 *   id = "str_pos",
 *   label = @Translation("Get position of sub-string"),
 *   description = @Translation("Get the position of a sub-string in a string"),
 *   category = "Text"
 * )
 */
class StrPos extends TamperBase {

  const SETTING_SUBSTRING = 'substring';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config[static::SETTING_SUBSTRING] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form[static::SETTING_SUBSTRING] = [
      '#type' => 'textfield',
      '#title' => $this->t('String to search for position'),
      '#default_value' => $this->getSetting('substring'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      static::SETTING_SUBSTRING => $form_state->getValue(static::SETTING_SUBSTRING),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    $substring = $this->getSetting(static::SETTING_SUBSTRING);
    return empty($substring) ? FALSE : mb_strpos($data, $substring);
  }

}
