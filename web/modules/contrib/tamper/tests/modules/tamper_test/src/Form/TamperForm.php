<?php

namespace Drupal\tamper_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\tamper\SourceDefinition;
use Drupal\tamper\TamperManagerInterface;
use Drupal\tamper\TamperPluginCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring a Tamper plugin.
 */
class TamperForm extends FormBase {

  /**
   * The Tamper plugin configuration.
   *
   * @var \Drupal\tamper\TamperPluginCollection
   */
  protected $pluginCollection;

  /**
   * The entity to add tamper configuration for.
   *
   * @var \Drupal\entity_test\Entity\EntityTestBundle
   */
  protected $entity;

  /**
   * The Tamper plugin.
   *
   * @var \Drupal\tamper\TamperInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tamper_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var self $form */
    $form = parent::create($container);
    $form->setTamperManager($container->get('plugin.manager.tamper'));
    return $form;
  }

  /**
   * Sets the tamper manager.
   *
   * @param \Drupal\tamper\TamperManagerInterface $tamper_manager
   *   Tamper plugin manager.
   */
  public function setTamperManager(TamperManagerInterface $tamper_manager) {
    $source_definition = new SourceDefinition([
      'foo' => 'Foo',
      'bar' => 'Bar',
      'baz' => 'Baz',
      'quxxie' => 'Qux',
    ]);
    $this->pluginCollection = new TamperPluginCollection($tamper_manager, $source_definition, []);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityTestBundle $entity_test_bundle = NULL, string $tamper_plugin_id = NULL) {
    $this->entity = $entity_test_bundle;

    // Instantiate plugin or get existing one.
    $existing_config = $this->entity->getThirdPartySetting('tamper_test', 'tampers');
    if ($existing_config) {
      $this->pluginCollection->setConfiguration($existing_config);
    }
    if (!$this->pluginCollection->has($tamper_plugin_id)) {
      $this->pluginCollection->addInstanceId($tamper_plugin_id, [
        'id' => $tamper_plugin_id,
      ]);
    }
    $this->plugin = $this->pluginCollection->get($tamper_plugin_id);

    // Get plugin's form.
    $form = $this->plugin->buildConfigurationForm($form, $form_state);

    // Display tamper plugin label.
    $form['plugin_label'] = [
      '#theme' => 'item',
      '#markup' => '<h2>' . $this->plugin->getPluginDefinition()['label'] . '</h2>',
      '#weight' => -100,
    ];

    $form['submit'] = [
      '#value' => $this->t('Submit'),
      '#type' => 'submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->submitConfigurationForm($form, $form_state);

    // Add plugin ID to config.
    $config = $this->pluginCollection->getConfiguration();
    foreach ($config as $key => $value) {
      $config[$key]['id'] = $key;
    }

    // Add tamper instances to the entity.
    $this->entity->setThirdPartySetting('tamper_test', 'tampers', $config);
    $this->entity->save();

    $this->messenger()->addStatus($this->t('Configuration saved.'));
  }

}
