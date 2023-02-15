<?php

// phpcs:ignoreFile

// Place in app/, adjacent to web/ and vendor.

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use dpi\DrupalPhpunitBootstrap\Utility;
use Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter;

$loader = require __DIR__ . '/vendor/autoload.php';
assert($loader instanceof ClassLoader);

foreach ([
           'Drupal\\BuildTests',
           'Drupal\\Tests',
           'Drupal\\TestSite',
           'Drupal\\KernelTests',
           'Drupal\\FunctionalTests',
           'Drupal\\FunctionalJavascriptTests',
           'Drupal\\TestTools',
         ] as $ns) {
  $loader->add($ns, __DIR__ . '/core/tests');
}

foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
  // Some directories dont exist. E.g the drupal/core subtree split project we bring in references
  // path ("Drupal\\Driver\\": "../drivers/lib/Drupal/Driver") outside of its repository.
  $paths = array_filter($paths, function (string $path): bool {
    return is_dir($path);
  });
  $loader->setPsr4($prefix, $paths);
}

$dirs = [];
foreach ([
           __DIR__ . '/core/modules',
           __DIR__ . '/core/profiles',
           __DIR__ . '/core/themes',
           __DIR__ . '/modules/project',
         ] as $dir) {
  $dirs = array_merge($dirs, Utility::drupal_phpunit_find_extension_directories($dir));
}

foreach (Utility::drupal_phpunit_get_extension_namespaces($dirs) as $prefix => $paths) {
  $loader->addPsr4($prefix, $paths);
}

date_default_timezone_set('Australia/Sydney');
if (class_exists('\Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter')) {
  ClassWriter::mutateTestBase($loader);
}

