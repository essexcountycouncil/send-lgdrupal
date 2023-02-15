<?php

namespace Drupal\localgov_directories\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin description.
 *
 * @EntityReferenceSelection(
 *   id = "localgov_directories_channels_selection",
 *   label = @Translation("LocalGov: Directories channels selection"),
 *   group = "localgov_directories_channels_selection",
 *   entity_types = {"node"},
 *   weight = 0
 * )
 */
class LocalgovDirectoriesChannelsSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager = NULL, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository = NULL) {
    $configuration['target_bundles'] = NULL;
    $configuration['auto_create'] = NULL;
    $configuration['auto_create_bundle'] = NULL;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'sort' => [
        'field' => '_none',
        'direction' => 'ASC',
      ],
    ] + parent::defaultConfiguration();
    unset($config['target_bundles']);
    unset($config['auto_create']);
    unset($config['auto_create_bundle']);
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['target_bundles']);
    unset($form['auto_create']);
    unset($form['auto_create_bundle']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // If the js didn't update the form.
    $form_state->unsetValue(['settings', 'handler_settings', 'target_bundles']);
    $form_state->unsetValue(['settings', 'handler_settings', 'auto_create']);
    $form_state->unsetValue([
      'settings',
      'handler_settings',
      'auto_create_bundle',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $query->condition('type', 'localgov_directory');
    $or = $query->orConditionGroup();
    $or->notExists('localgov_directory_channel_types');
    if ($this->configuration['entity']) {
      // The field can be instantiated without an entity.
      // The entity is not really part of the configuration.
      // Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface::getSelectionHandler
      // In practical situations this is used for forms etc. before the
      // configuration has been made, not when the field is on an entity type.
      // Really it would be nicer to be able to get to the bundle associated
      // with the configuration as there has to be one!
      $bundle = $this->configuration['entity']->bundle();
      $or->condition('localgov_directory_channel_types', $bundle, 'IN');
    }
    $query->condition($or);
    return $query;
  }

}
