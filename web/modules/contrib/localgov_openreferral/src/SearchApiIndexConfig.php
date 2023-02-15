<?php

namespace Drupal\localgov_openreferral;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\localgov_openreferral\Entity\PropertyMappingInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\PluginHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper to maintain search index for enabled entities.
 */
class SearchApiIndexConfig implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Search API Plugin Helper.
   *
   * @var \Drupal\search_api\Utility\PluginHelperInterface
   */
  protected $searchApiPluginHelper;

  /**
   * SearchApiIndexConfig constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\search_api\Utility\PluginHelperInterface $search_api_plugin_helper
   *   Search API plugin helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, PluginHelperInterface $search_api_plugin_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->searchApiPluginHelper = $search_api_plugin_helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('search_api.plugin_helper')
    );
  }

  /**
   * Add mapped service content to the services index.
   */
  public function addToServicesIndex(PropertyMappingInterface $map) {
    $index = $this->entityTypeManager->getStorage('search_api_index')->load('openreferral_services');
    if (!($index instanceof IndexInterface)) {
      $this->messenger->addError($this->t('Open Referral services index missing.'));
      return;
    }
    $entity_type_id = $map->mappedEntityType();
    $entity_bundle = $map->mappedBundle();

    try {
      $datasource = $index->getDatasource('entity:' . $entity_type_id);
    }
    catch (SearchApiException $e) {
      // Index::getDatasource() throws an exception if the datasource doesn't
      // exist yet.
      $datasource = $this->searchApiPluginHelper->createDatasourcePlugin($index, 'entity:' . $entity_type_id);
      $index = $index->addDatasource($datasource);
    }
    if (!$datasource) {
      $this->messenger->addError($this->t('Failed to update the open referral services search index.'));
      return;
    }

    $configuration = $datasource->getConfiguration();
    $configuration['bundles']['default'] = FALSE;
    if (!in_array($entity_bundle, $configuration['bundles']['selected'])) {
      $configuration['bundles']['selected'][] = $entity_bundle;
    }
    $datasource->setConfiguration($configuration);

    $index_field = $index->getField('rendered_item');
    if ($index_field) {
      $configuration = $index_field->getConfiguration();
      // Nod to the fact that we expect these to be directory items. This
      // could be the search view mode, but any folks implementing the
      // non-directory case could edit the config, or implement directory_index.
      // @todo where should this be documented.
      $configuration['view_mode']['entity:' . $entity_type_id][$entity_bundle] = 'directory_index';
      $index_field->setConfiguration($configuration);
    }

    $index->save();
  }

  /**
   * Remove content type from service index as mapping deleted.
   */
  public function removeFromServicesIndex(PropertyMappingInterface $map) {
    $index = $this->entityTypeManager->getStorage('search_api_index')->load('openreferral_services');
    if (!($index instanceof IndexInterface)) {
      $this->messenger->addError($this->t('Open Referral services index missing.'));
      return;
    }
    $entity_type_id = $map->mappedEntityType();
    $entity_bundle = $map->mappedBundle();

    try {
      $datasource = $index->getDatasource('entity:' . $entity_type_id);
    }
    catch (SearchApiException $e) {
      // Index::getDatasource() throws an exception if the datasource doesn't
      // exist.
      return;
    }

    $configuration = $datasource->getConfiguration();
    $configuration['bundles']['default'] = FALSE;
    if (($key = array_search($entity_bundle, $configuration['bundles']['selected'])) !== FALSE) {
      unset($configuration['bundles']['selected'][$key]);
    }
    $datasource->setConfiguration($configuration);
    $index->save();
  }

}
