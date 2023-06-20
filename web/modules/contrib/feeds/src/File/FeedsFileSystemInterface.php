<?php

namespace Drupal\feeds\File;

use Drupal\feeds\FeedInterface;

/**
 * Interface to interact with a Feeds files.
 */
interface FeedsFileSystemInterface {

  /**
   * Returns the uri of the configured feeds directory.
   *
   * @return string
   *   The configured directory. This can be for example
   *   "private://feeds/in_progress".
   */
  public function getFeedsDirectory(): string;

  /**
   * Saves data to the specified file relative to the feeds directory.
   *
   * @param mixed $data
   *   The data to save to a file.
   * @param string $filename
   *   The path to the file to save the data to, relative to the feeds
   *   directory.
   *
   * @return string
   *   The file uri the data was saved to. This includes the uri to the feeds
   *   directory.
   */
  public function saveData($data, string $filename): string;

  /**
   * Creates a new temporary file.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed that the temporary file will belong to.
   * @param string $prefix
   *   The prefix of the generated temporary filename.
   *   Note: Windows uses only the first three characters of prefix.
   *
   * @return string|bool
   *   The new temporary filename, or FALSE on failure.
   */
  public function tempnam(FeedInterface $feed, string $prefix = '');

  /**
   * Cleans up all files from the specified directory.
   *
   * @param string $subdirectory
   *   The subdirectory to empty.
   *
   * @return bool
   *   True if anything was deleted. False otherwise.
   */
  public function removeFiles(string $subdirectory): bool;

}
