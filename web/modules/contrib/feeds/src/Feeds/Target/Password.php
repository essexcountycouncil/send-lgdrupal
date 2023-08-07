<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a password field mapper.
 *
 * @FeedsTarget(
 *   id = "password",
 *   field_types = {"password"}
 * )
 */
class Password extends FieldTargetBase implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * Unencrypted password.
   */
  const PASS_UNENCRYPTED = 'plain';

  /**
   * MD5 encrypted password.
   */
  const PASS_MD5 = 'md5';

  /**
   * SHA512 encrypted password.
   */
  const PASS_SHA512 = 'sha512';

  /**
   * The password hash service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordHasher;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new Password object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Password\PasswordInterface $password_hasher
   *   The password hash service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PasswordInterface $password_hasher, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->passwordHasher = $password_hasher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if ($container->has('password_hasher')) {
      $password_hasher = $container->get('password_hasher');
    }
    else {
      $password_hasher = $container->get('password');
    }
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $password_hasher,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->setDescription('Password of this user.');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // If the value isn't set or isn't a string, we can't work with it.
    if (!isset($values['value']) || !is_string($values['value'])) {
      return;
    }

    $values['value'] = trim($values['value']);
    switch ($this->configuration['pass_encryption']) {
      case static::PASS_UNENCRYPTED:
        $values['pre_hashed'] = FALSE;
        break;

      case static::PASS_MD5:
        $new_hash = $this->passwordHasher->hash($values['value']);
        if (!$new_hash) {
          throw new TargetValidationException($this->t('Failed to hash the password.'));
        }
        // Indicate an updated password.
        $values['value'] = 'U' . $new_hash;
        $values['pre_hashed'] = TRUE;
        break;

      case static::PASS_SHA512:
        $values['pre_hashed'] = TRUE;
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'pass_encryption' => static::PASS_UNENCRYPTED,
    ];
  }

  /**
   * Returns if importing hashed passwords is supported.
   *
   * @return bool
   *   True if it is supported, false otherwise.
   */
  protected function hasPhpass(): bool {
    return version_compare(\Drupal::VERSION, 10.1, '<') || $this->moduleHandler->moduleExists('phpass');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['pass_encryption'] = [
      '#type' => 'radios',
      '#title' => $this->t('Password encryption'),
      '#options' => $this->encryptionOptions(),
      '#default_value' => $this->configuration['pass_encryption'],
    ];

    if (!$this->hasPhpass()) {
      $form['pass_encryption'][self::PASS_MD5]['#disabled'] = TRUE;
      $form['pass_encryption'][self::PASS_SHA512]['#disabled'] = TRUE;
      $form['warning'] = [
        '#plain_text' => $this->t('You need to enable the Password Compatibility module in order to import hashed passwords.'),
      ];
    }

    return $form;
  }

  /**
   * Returns the list of available password encryption methods.
   *
   * @return array
   *   An array of password encryption option titles.
   *
   * @see passFormCallback()
   */
  protected function encryptionOptions() {
    return [
      self::PASS_UNENCRYPTED => $this->t('Unencrypted'),
      self::PASS_MD5 => $this->t('MD5 (used in Drupal 6)'),
      self::PASS_SHA512 => $this->t('SHA512 hashed (used from Drupal 7 until Drupal 10.0)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    switch ($this->configuration['pass_encryption']) {
      case static::PASS_UNENCRYPTED:
        $summary[] = $this->t('Passwords are in plain text format.');
        break;

      case static::PASS_MD5:
        if ($this->hasPhpass()) {
          $summary[] = $this->t('Passwords are in MD5 format.');
        }
        else {
          $summary[] = [
            '#prefix' => '<div class="messages messages--warning">',
            '#markup' => $this->t('You need to enable the Password Compatibility module in order to import hashed passwords.'),
            '#suffix' => '</div>',
          ];
        }
        break;

      case static::PASS_SHA512:
        if ($this->hasPhpass()) {
          $summary[] = $this->t('Passwords are pre-hashed.');
        }
        else {
          $summary[] = [
            '#prefix' => '<div class="messages messages--warning">',
            '#markup' => $this->t('You need to enable the Password Compatibility module in order to import hashed passwords.'),
            '#suffix' => '</div>',
          ];
        }
        break;
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();

    // Add a dependency to the Password Compatibility module if a certain type
    // of encryption is selected.
    if ($this->moduleHandler->moduleExists('phpass')) {
      switch ($this->configuration['pass_encryption']) {
        case static::PASS_MD5:
        case static::PASS_SHA512:
          $this->dependencies['module'][] = 'phpass';
          break;
      }
    }

    return $this->dependencies;
  }

}
