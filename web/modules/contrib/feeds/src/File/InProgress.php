<?php

namespace Drupal\feeds\File;

/**
 * Deals with files to import in the 'in progress' directory.
 */
class InProgress extends FeedsFileSystemBase {

  /**
   * The default directory for saving files that are in progress.
   *
   * @var string
   */
  const DEFAULT_DIR = 'feeds/in_progress';

  /**
   * {@inheritdoc}
   */
  public function getFeedsDirectory(): string {
    $dir = $this->config->get('in_progress_dir');
    if ($dir) {
      return $dir;
    }

    return $this->getDefaultDirectory();
  }

}
