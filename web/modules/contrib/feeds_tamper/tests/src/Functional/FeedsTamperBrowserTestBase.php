<?php

namespace Drupal\Tests\feeds_tamper\Functional;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Provides a base class for Feeds Tamper functional tests.
 */
abstract class FeedsTamperBrowserTestBase extends FeedsBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'feeds',
    'feeds_tamper',
    'feeds_tamper_test',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an user with Feeds admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'administer feeds_tamper',
    ]);
    $this->drupalLogin($this->adminUser);
  }

}
