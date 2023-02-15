<?php

namespace Drupal\localgov_directories\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * LocalGov: Directories event subscriber.
 */
class DirectoriesConfigSubscriber implements EventSubscriberInterface {

  /**
   * Configuration prefixes to ignore.
   *
   * @var array
   */
  const PREFIXES = ['localgov_directories.localgov_directories_facets_type.'];

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The sync config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * DirectoriesConfigSubscriber constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config active storage.
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The sync config storage.
   */
  public function __construct(StorageInterface $config_storage, StorageInterface $sync_storage) {
    $this->activeStorage = $config_storage;
    $this->syncStorage = $sync_storage;
  }

  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    $transformation_storage = $event->getStorage();
    $destination_storage = $this->activeStorage;

    // If there is a configuration in the destination storage (the database)
    // which is not in the transformation storage (from directory) don't delete
    // it.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $this->activeStorage->getAllCollectionNames()) as $collection_name) {
      $transformation_collection = $transformation_storage->createCollection($collection_name);
      $destination_collection = $destination_storage->createCollection($collection_name);
      foreach ($this->ignoreConfigNames($destination_storage) as $config_name) {
        if (!$transformation_collection->exists($config_name) && $destination_collection->exists($config_name)) {
          // Make sure the config is not removed if it exists.
          $transformation_collection->write($config_name, $destination_collection->read($config_name));
        }
      }
    }

  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    // Export all changes if
    // $settings['localgov_directories_stage_site'] = TRUE.
    if (Settings::get('localgov_directories_stage_site')) {
      return;
    }

    $transformation_storage = $event->getStorage();
    $destination_storage = $this->syncStorage;

    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $transformation_storage->getAllCollectionNames()) as $collection_name) {
      $transformation_collection = $transformation_storage->createCollection($collection_name);
      $destination_collection = $destination_storage->createCollection($collection_name);
      foreach ($this->ignoreConfigNames($transformation_collection) as $config_name) {
        // If a configuration exists in the destination (configuration
        // directory) already leave it as it is, even if there are changes in
        // the transformation_storage (from the database).
        if ($destination_collection->exists($config_name)) {
          $transformation_collection->write($config_name, $destination_collection->read($config_name));
        }
        else {
          // Otherwise the configuration does not exist in the destination
          // (configuration directory) and should not be written to it from the
          // transformation_storage (as it is in the database).
          $transformation_collection->delete($config_name);
        }
      }
    }

  }

  /**
   * List all the configuration names to ignore in the storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The config storage to retreive all the key names from.
   *
   * @return string[]
   *   Yields the key names matching in the storage.
   */
  protected function ignoreConfigNames(StorageInterface $storage) {
    foreach (self::PREFIXES as $prefix) {
      foreach ($storage->listAll($prefix) as $name) {
        yield $name;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['onImportTransform'];
    $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['onExportTransform'];
    return $events;
  }

}
