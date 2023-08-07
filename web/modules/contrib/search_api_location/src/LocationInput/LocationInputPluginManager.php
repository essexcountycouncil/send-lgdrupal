<?php

namespace Drupal\search_api_location\LocationInput;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines a plugin manager for Location Inputs.
 */
class LocationInputPluginManager extends DefaultPluginManager {

  /**
   * Static cache for the location input definitions.
   *
   * @var string[][]
   *
   * @see \Drupal\search_api_location\LocationInput\LocationInputPluginManager::getInstances()
   */
  protected $locationInputMethods;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_location/location_input', $namespaces, $module_handler, 'Drupal\search_api_location\LocationInput\LocationInputInterface', 'Drupal\search_api_location\Annotation\LocationInput');
    $this->alterInfo('search_api_location_input_info');
    $this->setCacheBackend($cache_backend, 'search_api_location_input_backends');
  }

  /**
   * Returns all known location input methods.
   *
   * @return \Drupal\search_api_location\LocationInput\LocationInputInterface[]
   *   An array of data type plugins, keyed by type identifier.
   */
  public function getInstances() {
    if (!isset($this->locationInputMethods)) {
      $this->locationInputMethods = [];

      foreach ($this->getDefinitions() as $name => $data_type_definition) {
        if (class_exists($data_type_definition['class']) && empty($this->locationInputMethods[$name])) {
          $data_type = $this->createInstance($name);
          $this->locationInputMethods[$name] = $data_type;
        }
      }
    }

    return $this->locationInputMethods;
  }

  /**
   * Returns all location input methods as an options list.
   *
   * @return string[]
   *   An associative array with all recognized types as keys, mapped to their
   *   translated display names.
   *
   * @see \Drupal\search_api_location\LocationInput\LocationInputPluginManager::getInstances()
   */
  public function getInstancesOptions() {
    $options = [];
    foreach ($this->getDefinitions() as $id => $plugin) {
      $options[$id] = $plugin['label'];
    }
    return $options;
  }

}
