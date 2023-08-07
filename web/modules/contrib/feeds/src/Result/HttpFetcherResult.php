<?php

namespace Drupal\feeds\Result;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\File\FileSystemInterface;

/**
 * The default fetcher result object.
 */
class HttpFetcherResult extends FetcherResult implements HttpFetcherResultInterface {

  use DependencySerializationTrait;

  /**
   * The HTTP headers.
   *
   * @var array
   */
  protected $headers;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an HttpFetcherResult object.
   *
   * @param string $file_path
   *   The path to the result file.
   * @param array $headers
   *   An array of HTTP headers.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   (optional) The file system service.
   */
  public function __construct($file_path, array $headers, FileSystemInterface $file_system = NULL) {
    parent::__construct($file_path);
    $this->headers = array_change_key_case($headers);
    if (is_null($file_system)) {
      $file_system = \Drupal::service('file_system');
    }
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUp() {
    if ($this->filePath) {
      $this->fileSystem->unlink($this->filePath);
    }
  }

}
