<?php

declare(strict_types = 1);

namespace Drupal\localgov_directories_location;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\field\FieldConfigInterface;
use Drupal\localgov_directories\Constants as Directory;
use Drupal\search_api\IndexInterface as SearchIndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Proximity search configuration operations.
 *
 * Proximity search setup operations are:
 * - Add the localgov_location field to the Directory search index.
 * - Add the Proximity search activation field to the Directory channel content
 *   type.
 * - Add a new display for Proximity search to the Directory channel listing
 *   view.
 * - If necessary, restore the Directory channel selection field and the
 *   Directory title sort field to the Directory search index.
 */
class ProximitySearchSetup implements ContainerInjectionInterface {

  /**
   * Carry out all the operations.
   */
  public function setup(FieldConfigInterface $field, SearchIndexInterface $index): bool {

    if (!$this->hasLocationSearch($index->id())) {
      return FALSE;
    }

    $field_name = $field->getName();
    $is_node_entity_type = $field->getTargetEntityTypeId() === 'node';
    if ($field_name !== Directory::LOCATION_FIELD || !$is_node_entity_type || $index->getField(Directory::LOCATION_FIELD)) {
      return FALSE;
    }

    if ($this->setupLocationSearch($field, $index)) {
      // Now that the location field is part of the Directories search index, we
      // can add it to the Directory channel view.
      $this->addViewsDisplay();

      $this->addActivationField();

      // The channel selection and title sort fields may have gone missing.
      $this->repairSearchIndex($index);
    }
    else {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sets up indexing for location field.
   *
   * Adds the localgov_location field to the Directories search index.
   */
  public function setupLocationSearch(FieldConfigInterface $field, SearchIndexInterface $index): bool {

    if ($index->getField(Directory::LOCATION_FIELD)) {
      return FALSE;
    }

    $index_datasrc = $index->getDatasource('entity:node');
    if (empty($index_datasrc)) {
      return FALSE;
    }

    $entity_bundle = $field->getTargetBundle();
    $datasrc_cfg = $index_datasrc->getConfiguration();
    $is_bundle_indexed = in_array($entity_bundle, $datasrc_cfg['bundles']['selected']);
    if (!$is_bundle_indexed) {
      $datasrc_cfg['bundles']['selected'][] = $entity_bundle;
      $index_datasrc->setConfiguration($datasrc_cfg);
    }

    $location_field = new SearchIndexField($index, Directory::LOCATION_FIELD);
    $location_field->setLabel('Location » Geo » location');
    $location_field->setDatasourceId('entity:node');
    $location_field->setType('location');
    $location_field->setPropertyPath('localgov_location:entity:location');
    $index->addField($location_field);
    $index->save();
    $index->reindex();

    return TRUE;
  }

  /**
   * Adds the Proximity search activation field to the Directory channel.
   *
   * Also places this field in the Directory channel's form display.
   */
  public function addActivationField(): void {

    if (!$this->etm->getStorage('field_storage_config')->load('node' . '.' . Directory::PROXIMITY_SEARCH_CFG_FIELD)) {
      $proximity_search_cfg_field_storage = Yaml::decode(file_get_contents($this->modulePath . '/config/install/field.storage.node.localgov_proximity_search_cfg.yml'));
      $this->etm->getStorage('field_storage_config')->create($proximity_search_cfg_field_storage)->save();
    }

    if (!$this->etm->getStorage('field_config')->load('node' . '.' . Directory::CHANNEL_NODE_BUNDLE . '.' . Directory::PROXIMITY_SEARCH_CFG_FIELD)) {
      $proximity_search_cfg_field = Yaml::decode(file_get_contents($this->modulePath . '/config/conditional/field.field.node.localgov_directory.localgov_proximity_search_cfg.yml'));
      $this->etm->getStorage('field_config')->create($proximity_search_cfg_field)->save();

      $this->addActivationFieldDisplay();
    }
  }

  /**
   * Sets up display for the proximity config field.
   *
   * - Adds the Proximity search config field to the Directory channel form.
   * - Hides it from Directory channel entity view.
   */
  public function addActivationFieldDisplay(): void {

    // Add Proximity search activation field to Directory channel form.
    $dir_channel_form_display = $this->etm->getStorage('entity_form_display')->load('node' . '.' . Directory::CHANNEL_NODE_BUNDLE . '.default');
    if (!$dir_channel_form_display->getComponent(Directory::PROXIMITY_SEARCH_CFG_FIELD)) {
      $dir_channel_form_display_with_proximity_search = Yaml::decode(file_get_contents($this->modulePath . '/config/override/core.entity_form_display.node.localgov_directory.default.yml'));

      if (isset($dir_channel_form_display_with_proximity_search['content'][Directory::PROXIMITY_SEARCH_CFG_FIELD])) {
        $dir_channel_form_display->setComponent(Directory::PROXIMITY_SEARCH_CFG_FIELD, $dir_channel_form_display_with_proximity_search['content'][Directory::PROXIMITY_SEARCH_CFG_FIELD])->save();
      }
    }

    // Hide the Proximity search activation field from Directory channel.
    $dir_channel_view_display = $this->etm->getStorage('entity_view_display')->load('node' . '.' . Directory::CHANNEL_NODE_BUNDLE . '.default');
    if (!$dir_channel_view_display->getComponent(Directory::PROXIMITY_SEARCH_CFG_FIELD)) {
      $dir_channel_view_display->removeComponent(Directory::PROXIMITY_SEARCH_CFG_FIELD)->save();
    }
  }

  /**
   * Adds the Proximity search display to the Directory channel view.
   *
   * Also adds the Proximity search filter to the existing Map display of this
   * view.
   */
  public function addViewsDisplay(): void {

    $view = $this->etm->getStorage('view')->load(Directory::CHANNEL_VIEW);
    if (empty($view) || $view->getDisplay(Directory::CHANNEL_VIEW_PROXIMITY_SEARCH_DISPLAY)) {
      return;
    }

    $view_with_proximity_search = Yaml::decode(file_get_contents($this->modulePath . '/config/override/views.view.localgov_directory_channel.yml'));

    $displays = $view->get('display');
    $displays[Directory::CHANNEL_VIEW_PROXIMITY_SEARCH_DISPLAY] = $view_with_proximity_search['display'][Directory::CHANNEL_VIEW_PROXIMITY_SEARCH_DISPLAY];
    $displays[Directory::CHANNEL_VIEW_MAP_DISPLAY] = $view_with_proximity_search['display'][Directory::CHANNEL_VIEW_MAP_DISPLAY];
    $view->set('display', $displays);
    $view->save();
  }

  /**
   * Restores missing search index fields.
   *
   * If needed, adds the channel selection and the title sort fields to the
   * search index.  These fields may have gone missing from the Directory search
   * index when the index was updated while these fields were *not* attached to
   * any indexed node bundle.
   */
  public function repairSearchIndex(SearchIndexInterface $index): void {

    if (!$index->getField(Directory::CHANNEL_SELECTION_FIELD)) {
      $channel_field = new SearchIndexField($index, Directory::CHANNEL_SELECTION_FIELD);
      $channel_field->setLabel('Directory channels');
      $channel_field->setDatasourceId('entity:node');
      $channel_field->setType('integer');
      $channel_field->setPropertyPath(Directory::CHANNEL_SELECTION_FIELD);
      $index->addField($channel_field);
      $index->save();
      $index->reindex();
    }

    if (!$index->getField(Directory::TITLE_SORT_FIELD)) {
      $sort_title_field = new SearchIndexField($index, Directory::TITLE_SORT_FIELD);
      $sort_title_field->setLabel('Title (sort)');
      $sort_title_field->setDatasourceId('entity:node');
      $sort_title_field->setType('string');
      $sort_title_field->setPropertyPath(Directory::TITLE_SORT_FIELD);
      $index->addField($sort_title_field);
      $index->save();
      $index->reindex();
    }
  }

  /**
   * Is the Search index ready for location search?
   *
   * Criteria for readiness:
   * - Search index is in "enabled" state.
   * - Search backend of the search index supports location search.
   */
  public function hasLocationSearch(string $search_index_name = Directory::DEFAULT_INDEX): bool {

    $search_index = $this->etm->getStorage('search_api_index')->load($search_index_name);
    if (empty($search_index) || !$search_index->status()) {
      return FALSE;
    }

    $search_server = $search_index->getServerInstance();
    $has_location_search = $search_server ? $search_server->supportsDataType(Directory::SEARCH_API_LOCATION_DATATYPE) : FALSE;

    return $has_location_search;
  }

  /**
   * Factory method.
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * Initializes dependencies.
   */
  public function __construct(EntityTypeManagerInterface $etm, ModuleExtensionList $module_ext_list) {

    $this->etm = $etm;
    $this->modulePath = $module_ext_list->getPath(Directory::LOCATION_MODULE);
  }

  /**
   * Entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $etm;

  /**
   * Path of this module relative to docroot.
   *
   * @var string
   */
  protected string $modulePath = '';

}
