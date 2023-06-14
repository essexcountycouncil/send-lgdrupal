<?php

namespace Drupal\feeds\File;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\feeds\FeedInterface;

/**
 * A service to interact with Feeds files.
 */
abstract class FeedsFileSystemBase implements FeedsFileSystemInterface {

  /**
   * The Feeds configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The file and stream wrapper helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new FeedsFileSystem object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file and stream wrapper helper.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->config = $config_factory->get('feeds.settings');
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function saveData($data, string $filename): string {
    $destination = $this->getFeedsDirectory() . '/' . $filename;
    $directory = $this->fileSystem->dirname($destination);
    $this->prepareDirectory($directory);

    $this->fileSystem->saveData($data, $destination);
    return $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function tempnam(FeedInterface $feed, string $prefix = '') {
    if (empty($prefix)) {
      $prefix = 'feeds_file_';
    }

    $directory = $this->getFeedsDirectory() . '/' . $feed->id();
    $this->prepareDirectory($directory);
    $path = $this->fileSystem->realpath($directory);
    return tempnam($path, $prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function removeFiles(string $subdirectory): bool {
    $directory = $this->getFeedsDirectory() . '/' . $subdirectory;
    if (!is_dir($directory)) {
      // Directory does not exist. Abort.
      return FALSE;
    }
    $result = $this->fileSystem->deleteRecursive($directory);
    return $result ? TRUE : FALSE;
  }

  /**
   * Returns the default directory.
   *
   * @return string
   *   The default directory.
   */
  protected function getDefaultDirectory(): string {
    $schemes = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::VISIBLE);
    $scheme = isset($schemes['private']) ? 'private' : 'public';
    return $scheme . '://' . static::DEFAULT_DIR;
  }

  /**
   * Prepares the specified directory for writing files to it.
   *
   * The directory gets created in case it doesn't exist yet.
   *
   * @param string $dir
   *   The directory to prepare.
   *
   * @throws \RuntimeException
   *   In case the directory could not be created or made writable.
   */
  protected function prepareDirectory(string $dir) {
    if (!$this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new \RuntimeException(t('Feeds directory either cannot be created or is not writable.'));
    }
  }

}
