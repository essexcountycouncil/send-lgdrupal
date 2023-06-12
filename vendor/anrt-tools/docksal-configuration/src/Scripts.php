<?php

namespace Anrt\Tools\DocksalConfiguration;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class Scripts {

  public static function postUpdate(Event $event) {
    $composer = $event->getComposer();

    // Find the directory above this file's directory.
    $originDirectory = dirname(__DIR__);
    // Find the directory above the vendor directory.
    $projectRoot = dirname($composer->getConfig()->get('vendor-dir'));

    // Docksal
    $filesystem = new Filesystem();
    // Copy all docksal files into project.
    $filesystem->mirror(
      $originDirectory . '/.docksal',
      $projectRoot . '/.docksal',
      null,
      ['override' => true],
    );
    // Ensure commands are executable, avoids the annoying
    // "[cmd] is not set to be executable" "Fix automatically?"
    $filesystem->chmod($projectRoot . '/.docksal/commands', 0775, 0000, true);
    // Ensure non-project related docksal files are ignored.
    $filesystem->copy(
      $originDirectory . '/docksal.gitignore',
      $projectRoot . '/.docksal/.gitignore'
    );

    // DDEV
    $filesystem = new Filesystem();
    // Copy all ddev files into project.
    $filesystem->mirror(
      $originDirectory . '/.ddev',
      $projectRoot . '/.ddev',
      null,
      ['override' => true],
    );
  }

}
