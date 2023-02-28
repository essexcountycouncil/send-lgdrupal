<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * A plugin for performing a multiline search/replace.
 *
 * @Tamper(
 *   id = "find_replace_multiline",
 *   label = @Translation("Find replace (multiline)"),
 *   description = @Translation("Find and replace text, with multiple search/replacement patterns defined together."),
 *   category = "Text"
 * )
 */
class FindReplaceMultiline extends TamperBase {

  const SETTING_FIND_REPLACE = 'find_replace';
  const SETTING_SEPARATOR = 'separator';
  const SETTING_CASE_SENSITIVE = 'case_sensitive';
  const SETTING_WORD_BOUNDARIES = 'word_boundaries';
  const SETTING_WHOLE = 'whole';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_FIND_REPLACE] = [];
    $config[self::SETTING_SEPARATOR] = '|';
    $config[self::SETTING_CASE_SENSITIVE] = FALSE;
    $config[self::SETTING_WORD_BOUNDARIES] = FALSE;
    $config[self::SETTING_WHOLE] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_FIND_REPLACE] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to find and the replacements'),
      '#default_value' => implode("\n", $this->getSetting(self::SETTING_FIND_REPLACE)),
      '#description' => $this->t("Enter one match per line in the format <code>search|replacement</code>, though the separator can be changed below.\nThe replacements will be processed in order provided above."),
      '#required' => TRUE,
    ];

    $form[self::SETTING_SEPARATOR] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search/replacement value separator'),
      '#default_value' => $this->getSetting(self::SETTING_SEPARATOR),
      '#description' => $this->t('Control the character used to separate the "search" from the "replace" string in the field above. Defaults to "|", to match the value separator used on the Drupal core list fields.'),
      '#required' => TRUE,
    ];

    $form[self::SETTING_CASE_SENSITIVE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive'),
      '#default_value' => $this->getSetting(self::SETTING_CASE_SENSITIVE),
      '#description' => $this->t('If checked, "book" will match "book" but not "Book" or "BOOK".'),
    ];

    $form[self::SETTING_WORD_BOUNDARIES] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Respect word boundaries'),
      '#default_value' => $this->getSetting(self::SETTING_WORD_BOUNDARIES),
      '#description' => $this->t('If checked, "book" will match "book" but not "bookcase".'),
    ];

    $form[self::SETTING_WHOLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match whole word/phrase'),
      '#default_value' => $this->getSetting(self::SETTING_WHOLE),
      '#description' => $this->t('If checked, then the whole word or phrase will be matched, e.g. "book" will match "book" but not "the book". If this option is selected then "Respect word boundaries" above will be ignored.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $lines = explode("\n", $form_state->getValue(self::SETTING_FIND_REPLACE));
    $separator = $form_state->getValue(self::SETTING_SEPARATOR);

    // Check if each line contains the separator.
    $missing = [];
    foreach ($lines as $index => $line) {
      $line = trim($line);
      if (empty($line)) {
        continue;
      }
      if (strpos($line, $separator) === FALSE) {
        $missing[] = $index + 1;
      }
    }

    if (!empty($missing)) {
      $amount = count($missing);
      if ($amount > 1) {
        $last = array_pop($missing);
      }
      else {
        $last = '';
      }

      $error_message = $this->formatPlural($amount, 'Line @line is missing the separator "@separator".', 'Lines @lines and @last_line are missing the separator "@separator".', [
        '@line' => reset($missing),
        '@lines' => implode(', ', $missing),
        '@last_line' => $last,
        '@separator' => $separator,
      ]);
      $form_state->setErrorByName(self::SETTING_FIND_REPLACE, $error_message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $lines = explode("\n", $form_state->getValue(self::SETTING_FIND_REPLACE));

    // Remove empty lines.
    foreach ($lines as $index => $line) {
      $line = trim($line);
      if (strlen($line) < 1) {
        $lines[$index] = $line;
      }
    }
    $lines = array_filter($lines);

    $this->setConfiguration([
      self::SETTING_FIND_REPLACE => $lines,
      self::SETTING_SEPARATOR => $form_state->getValue(self::SETTING_SEPARATOR),
      self::SETTING_CASE_SENSITIVE => $form_state->getValue(self::SETTING_CASE_SENSITIVE),
      self::SETTING_WORD_BOUNDARIES => $form_state->getValue(self::SETTING_WORD_BOUNDARIES),
      self::SETTING_WHOLE => $form_state->getValue(self::SETTING_WHOLE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    $function = $this->getFunction();

    $find_replace = $this->getSetting(self::SETTING_FIND_REPLACE);
    $separator = $this->getSetting(self::SETTING_SEPARATOR);

    // Process the find/replace string one line at a time.
    foreach ($find_replace as $line) {
      if (empty($line)) {
        continue;
      }
      // Verify the separator is found.
      if (strpos($line, $separator) === FALSE) {
        throw new TamperException(sprintf('In the configuration the string separator "%s" is missing.', $separator));
      }
      [$find, $replace] = explode($separator, $line);
      if ($this->useRegex()) {
        $find = $this->getRegexPattern($find);
      }
      $data = $function($find, $replace, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

  /**
   * Check if we are using the regex callback.
   *
   * @return bool
   *   TRUE when regex will be used.
   */
  protected function useRegex() {
    return $this->getSetting(self::SETTING_WORD_BOUNDARIES) || $this->getSetting(self::SETTING_WHOLE);
  }

  /**
   * Get the function to use for the find and replace.
   *
   * @return string
   *   Function name to call.
   */
  protected function getFunction() {
    if ($this->useRegex()) {
      return 'preg_replace';
    }
    return $this->getSetting(self::SETTING_CASE_SENSITIVE) ? 'str_replace' : 'str_ireplace';
  }

  /**
   * Get the regex pattern.
   *
   * @param string $find
   *   The string to be found.
   *
   * @return string
   *   Regex pattern to use.
   */
  protected function getRegexPattern($find) {
    $regex = $this->getSetting(self::SETTING_WHOLE) ?
      '/^' . preg_quote($find, '/') . '$/u' :
      '/\b' . preg_quote($find, '/') . '\b/u';

    if (!$this->getSetting(self::SETTING_CASE_SENSITIVE)) {
      $regex .= 'i';
    }
    return $regex;
  }

}
