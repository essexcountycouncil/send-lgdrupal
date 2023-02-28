<?php

namespace Drupal\webform_workflows_element\Form\AdminConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\AdminConfig\WebformAdminConfigBaseForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin advanced settings.
 */
class WebformAdminConfigWorkflowsForm extends WebformAdminConfigBaseForm {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The render cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_workflows_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform_workflows_element.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderCache = $container->get('cache.render');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->routerBuilder = $container->get('router.builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_workflows_element.settings');

    // UI.
    $form['ui'] = [
      '#type' => 'details',
      '#title' => $this->t('User interface settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['ui']['color_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Colors classes available for states and transitions'),
      '#description' => $this->t('Enter one per line in format "Name|CSS class". Note webforms can be viewed in both admin and user-facing themes, so it may be necessary to add the class to both themes to be consistent.'),
      '#default_value' => $config->get('ui.color_options'),
    ];

    // Email / Handler: Mail.
    $form['mail'] = [
      '#type' => 'details',
      '#title' => $this->t('Email settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['mail']['default_body_text'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'text',
      '#title' => $this->t('Default workflow change email body (Plain text)'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_body_text'),
    ];
    $form['mail']['default_body_html'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'html',
      '#title' => $this->t('Default workflow change email body (HTML)'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_body_html'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $op = (string) $form_state->getValue('op');

    // Update config and submit form.
    $config = $this->config('webform_workflows_element.settings');
    $config->set('ui', $form_state->getValue('ui'));
    $config->set('mail', $form_state->getValue('mail'));
    $config->save();
  }
}
