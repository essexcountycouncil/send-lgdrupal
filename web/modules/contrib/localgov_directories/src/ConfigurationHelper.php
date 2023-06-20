<?php

declare(strict_types = 1);

namespace Drupal\localgov_directories;

use Drupal\block\BlockInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\FileStorage as ConfigFileStorage;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;
use Drupal\search_api\Utility\PluginHelperInterface;
use Drupal\views\ViewEntityInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Update index and block configurations for changed entities and fields.
 */
class ConfigurationHelper implements ContainerInjectionInterface {

  /**
   * The Search API directory index.
   *
   * @var \Drupal\search_api\Entity\IndexInterface
   */
  protected ?IndexInterface $index;

  /**
   * The directory view.
   *
   * @var \Drupal\views\ViewEntityInterface
   */
  protected ?ViewEntityInterface $view;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The Localgov Directories logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The configuration installer.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected ConfigInstallerInterface $configInstaller;

  /**
   * Search API Plugin Helper utility.
   *
   * @var \Drupal\search_api\Utility\PluginHelperInterface
   */
  protected PluginHelperInterface $searchApiPluginHelper;

  /**
   * DirectoryExtraFieldDisplay constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger channel factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   Module extension list.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   Config installer.
   * @param \Drupal\search_api\Utility\PluginHelperInterface $search_api_plugin_helper
   *   Search API Plugin helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, LoggerChannelFactoryInterface $logger_factory, ModuleExtensionList $module_extension_list, ConfigInstallerInterface $config_installer, PluginHelperInterface $search_api_plugin_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->logger = $logger_factory->get('localgov_directories');
    $this->moduleExtensionList = $module_extension_list;
    $this->configInstaller = $config_installer;
    $this->searchApiPluginHelper = $search_api_plugin_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('logger.factory'),
      $container->get('extension.list.module'),
      $container->get('config.installer'),
      $container->get('search_api.plugin_helper'),
    );
  }

  /**
   * Get index to work on.
   */
  public function getIndex(): ?IndexInterface {
    return $this->index ?? $this->entityTypeManager->getStorage('search_api_index')->load(Constants::DEFAULT_INDEX);
  }

  /**
   * Get directory view to work on.
   */
  public function getView(): ?ViewEntityInterface {
    return $this->view ?? $this->entityTypeManager->getStorage('view')->load('localgov_directory_channel');
  }

  /**
   * Act on a Directory channel field being added.
   */
  public function insertedDirectoryChannelField(FieldConfigInterface $field): void {
    $entity_type_id = $field->getTargetEntityTypeId();
    $entity_bundle = $field->getTargetBundle();
    // Index changes.
    if ($index = $this->getIndex()) {
      $this->indexAddBundle($index, $entity_type_id, $entity_bundle);
      $this->renderedItemAddBundle($index, $entity_type_id, $entity_bundle);
      $this->indexAddChannelsField($index);
      // The Channel is also the trigger for adding/removing from the index.
      // So also handle fields already existing on the entity that should be
      // included in the index.
      $entity_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $entity_bundle);
      if (array_key_exists(Constants::FACET_SELECTION_FIELD, $entity_fields)) {
        $this->indexAddFacetField($index);
      }
      if (array_key_exists(Constants::TITLE_SORT_FIELD, $entity_fields)) {
        $this->indexAddTitleSortField($index);
      }
      $index->save();
    }
    if ($view = $this->getView()) {
      $this->viewSetViewMode($view, $entity_type_id, $entity_bundle);
      $view->save();
    }
    $this->blockAddContentType(Constants::CHANNEL_SEARCH_BLOCK, $entity_bundle);
  }

  /**
   * Act on Directory channel field being removed.
   */
  public function deletedDirectoryChannelField(FieldConfigInterface $field): void {
    // Only working for nodes at the moment.
    $entity_type_id = $field->getTargetEntityTypeId();
    $entity_bundle = $field->getTargetBundle();
    // Index changes.
    if ($index = $this->getIndex()) {
      $this->indexRemoveBundle($index, $entity_type_id, $entity_bundle);
    }
    $this->blockRemoveContentType(Constants::CHANNEL_SEARCH_BLOCK, $entity_bundle);
  }

  /**
   * Act on Directory facet field being added.
   */
  public function insertedFacetField(FieldConfigInterface $field): void {
    if ($index = $this->getIndex()) {
      $this->indexAddFacetField($index);
      $index->save();
    }

    if ($index && $index->status()) {
      $this->createFacet(Constants::FACET_CONFIG_ENTITY_ID, Constants::FACET_CONFIG_FILE);
    }
  }

  /**
   * Act on Directory title sort field being added.
   */
  public function insertedTitleSortField(FieldConfigInterface $field): void {
    if ($index = $this->getIndex()) {
      $this->indexAddTitleSortField($index);
      $index->save();
    }
  }

  /**
   * Create new config entity from given config file.
   *
   * @param string $entity_type
   *   Example: facets_facet.
   * @param string $config_path
   *   Example: modules/foo/config/bar.
   * @param string $config_filename
   *   Example: views.view.bar.
   */
  public function importConfigEntity(string $entity_type, string $config_path, string $config_filename): bool {
    $config_src = new ConfigFileStorage($config_path);
    if (empty($config_src)) {
      return FALSE;
    }

    $config_values = $config_src->read($config_filename);
    if (empty($config_values)) {
      return FALSE;
    }

    try {
      $this->entityTypeManager->getStorage($entity_type)->create($config_values)->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create new config entity: %filename.  Error: %msg', [
        '%filename' => $config_filename,
        '%msg' => $e->getMessage(),
      ]);

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Setup indexing on the Facet selection field of Directory entries.
   *
   * This assumes that the localgov_directory_facets_select field is part of a
   * Directory entry content type.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to add the facet field to.
   */
  protected function indexAddFacetField(IndexInterface $index): void {
    if ($index->getField(Constants::FACET_INDEXING_FIELD)) {
      return;
    }

    $field = new SearchIndexField($index, Constants::FACET_INDEXING_FIELD);
    $field->setLabel('Facets');
    $field->setDataSourceId('entity:node');
    $field->setPropertyPath(Constants::FACET_SELECTION_FIELD);
    $field->setType('integer');
    $field->setDependencies([
      'config' => [
        'field.storage.node.' . Constants::FACET_SELECTION_FIELD,
      ],
    ]);
    $index->addField($field);
  }

  /**
   * Setup indexing on the Title Sort field of Directory entries.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to the title sort field field to.
   */
  protected function indexAddTitleSortField(IndexInterface $index): void {
    if ($index->getField(Constants::TITLE_SORT_FIELD)) {
      return;
    }

    $field = new SearchIndexField($index, Constants::TITLE_SORT_FIELD);
    $field->setLabel('Title (sort)');
    $field->setDataSourceId('entity:node');
    $field->setPropertyPath(Constants::TITLE_SORT_FIELD);
    $field->setType('string');
    $field->setDependencies([
      'config' => [
        'field.storage.node.' . Constants::TITLE_SORT_FIELD,
      ],
    ]);
    $index->addField($field);
  }

  /**
   * Setup indexing on the Directory channels field of Directory entries.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to the channel field to.
   */
  protected function indexAddChannelsField(IndexInterface $index): void {
    if ($index->getField(Constants::CHANNEL_SELECTION_FIELD)) {
      return;
    }

    $field = new SearchIndexField($index, Constants::CHANNEL_SELECTION_FIELD);
    $field->setLabel('Directory channels');
    $field->setDataSourceId('entity:node');
    $field->setPropertyPath(Constants::CHANNEL_SELECTION_FIELD);
    $field->setType('string');
    $field->setDependencies([
      'config' => [
        'field.storage.node.' . Constants::CHANNEL_SELECTION_FIELD,
      ],
    ]);
    $index->addField($field);
  }

  /**
   * Import config entity for the directory Facet.
   */
  public function createFacet(string $facet_id, string $facet_cfg_file): void {
    if ($this->entityTypeManager->getStorage('facets_facet')->load($facet_id)) {
      return;
    }

    $conditional_config_path = $this->moduleExtensionList->getPath('localgov_directories') . '/config/conditional';
    if ($this->importConfigEntity('facets_facet', $conditional_config_path, $facet_cfg_file)) {
      $this->configInstaller->installOptionalConfig(NULL, [
        'config' => 'facets.facet.localgov_directories_facets',
      ]);
    }
  }

  /**
   * Update a block's visibility to add to content type.
   *
   * The given block should appear in the sidebars of pages for the given
   * content type.
   *
   * @param string $block_id
   *   The block to update visibility for.
   * @param string $content_type
   *   The content type on which the block should be visible.
   *
   * @return bool
   *   True on success.
   */
  public function blockAddContentType(string $block_id, string $content_type): bool {
    $block_config = $this->entityTypeManager->getStorage('block')->load($block_id);
    if (!$block_config instanceof BlockInterface) {
      return FALSE;
    }

    try {
      $visibility = $block_config->getVisibility();
      $visibility['node_type']['bundles'][$content_type] = $content_type;
      $block_config->setVisibilityConfig('node_type', $visibility['node_type']);
      $block_config->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to add %content-type content type to %block-id block: %error-msg', [
        '%content-type' => $content_type,
        '%block-id' => $block_id,
        '%error-msg' => $e->getMessage(),
      ]);

      return FALSE;
    }

    $this->logger->notice('Added %content-type content type to %block-id block.', [
      '%content-type' => $content_type,
      '%block-id' => $block_id,
    ]);

    return TRUE;
  }

  /**
   * Update a block's visibility to remove from content type.
   *
   * The given block should no longer appear in the sidebars of pages for the
   * given content type.
   *
   * @param string $block_id
   *   The block to update visibility for.
   * @param string $content_type
   *   The content type on which the block should no longer be visible.
   *
   * @return bool
   *   True on success.
   */
  public function blockRemoveContentType(string $block_id, string $content_type): bool {
    $block_config = $this->entityTypeManager->getStorage('block')->load($block_id);
    if (!$block_config instanceof BlockInterface) {
      $this->logger->error('Block %block-id is missing.  Cannot update its visibility settings.', [
        '%block-id' => $block_id,
      ]);

      return FALSE;
    }

    try {
      $visibility = $block_config->getVisibility();
      if (empty($visibility['node_type']['bundles'][$content_type])) {
        return FALSE;
      }
      unset($visibility['node_type']['bundles'][$content_type]);
      $block_config->setVisibilityConfig('node_type', $visibility['node_type']);
      $block_config->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to remove %content-type content type to %block-id block: %error-msg', [
        '%content-type' => $content_type,
        '%block-id' => $block_id,
        '%error-msg' => $e->getMessage(),
      ]);

      return FALSE;
    }

    $this->logger->notice('Removed %content-type content type to %block-id block.', [
      '%content-type' => $content_type,
      '%block-id' => $block_id,
    ]);

    return TRUE;
  }

  /**
   * Add entity bundle to index datasource.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to add bundle to.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $entity_bundle
   *   The bundle ID.
   */
  protected function indexAddBundle(IndexInterface $index, string $entity_type_id, string $entity_bundle): void {
    $datasource = $this->indexGetDatasource($index, $entity_type_id);
    if (!$datasource) {
      $this->logger->error('Failed to update the directories search index with new bundle');
      return;
    }

    $configuration = $datasource->getConfiguration();
    $configuration['bundles']['default'] = FALSE;
    if (!in_array($entity_bundle, $configuration['bundles']['selected'])) {
      $configuration['bundles']['selected'][] = $entity_bundle;
    }
    $datasource->setConfiguration($configuration);
  }

  /**
   * Remove entity bundle to index datasource.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to remove bundle from.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $entity_bundle
   *   The bundle ID.
   */
  protected function indexRemoveBundle(IndexInterface $index, string $entity_type_id, string $entity_bundle): void {
    $datasource = $this->indexGetDatasource($index, $entity_type_id);
    if (!$datasource) {
      $this->logger->error('Failed to update the directories search index with new bundle');
      return;
    }

    $configuration = $datasource->getConfiguration();
    $configuration['bundles']['default'] = FALSE;
    if (($key = array_search($entity_bundle, $configuration['bundles']['selected'])) !== FALSE) {
      unset($configuration['bundles']['selected'][$key]);
    }
    $datasource->setConfiguration($configuration);
  }

  /**
   * Set entity bundle default view mode in view.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view to update.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $entity_bundle
   *   The bundle ID.
   */
  protected function viewSetViewMode(ViewEntityInterface $view, string $entity_type_id, string $entity_bundle): void {
    // Also set the default view mode for the directory view listing.
    $display = $view->get('display');
    if (isset($display['node_embed']['display_options']['row'])) {
      $display['node_embed']['display_options']['row']['options']['view_modes']['entity:' . $entity_type_id][$entity_bundle] = 'teaser';
    }
    elseif (isset($display['default']['display_options']['row'])) {
      $display['default']['display_options']['row']['options']['view_modes']['entity:' . $entity_type_id][$entity_bundle] = 'teaser';
    }
    $view->set('display', $display);
  }

  /**
   * Add entity bundle to index rendered item field.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to add bundle to.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $entity_bundle
   *   The bundle ID.
   */
  protected function renderedItemAddBundle(IndexInterface $index, string $entity_type_id, string $entity_bundle): void {
    $index_field = $index->getField('rendered_item');
    if ($index_field) {
      $configuration = $index_field->getConfiguration();
      $configuration['view_mode']['entity:' . $entity_type_id][$entity_bundle] = 'directory_index';
      $index_field->setConfiguration($configuration);
    }
  }

  /**
   * Get index entity datasource.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to retrieve the datasource from.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface
   *   The datasource.
   */
  protected function indexGetDatasource(IndexInterface $index, string $entity_type_id): DatasourceInterface {
    $datasource = $index->getDatasource('entity:' . $entity_type_id);
    if (!$datasource) {
      // If the content:node datasource has been lost so have the fields most
      // probably and it's more of a mess. But leaving this here anyway.
      $datasource = $this->searchApiPluginHelper->createDatasourcePlugin($index, 'entity:' . $entity_type_id);
    }

    return $datasource;
  }

}
