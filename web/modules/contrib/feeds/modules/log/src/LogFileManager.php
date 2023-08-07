<?php

namespace Drupal\feeds_log;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\feeds\File\FeedsFileSystemBase;

/**
 * Service for managing log files.
 */
class LogFileManager extends FeedsFileSystemBase implements LogFileManagerInterface {

  /**
   * The default directory for feeds log files.
   *
   * @var string
   */
  const DEFAULT_DIR = 'feeds/log';

  /**
   * The feeds log configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new LogFileManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager) {
    parent::__construct($config_factory, $file_system, $stream_wrapper_manager);
    $this->config = $config_factory->get('feeds_log.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedsDirectory(): string {
    $dir = $this->config->get('log_dir');
    if ($dir) {
      return $dir;
    }

    return $this->getDefaultDirectory();
  }

}
