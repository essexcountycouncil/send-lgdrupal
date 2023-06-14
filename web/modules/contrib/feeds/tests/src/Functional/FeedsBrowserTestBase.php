<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\feeds\FeedInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Provides a base class for Feeds functional tests.
 */
abstract class FeedsBrowserTestBase extends BrowserTestBase {

  use CronRunTrait;
  use FeedCreationTrait;
  use FeedsCommonTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type.
    $this->setUpNodeType();

    // Create an user with Feeds admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'access feed overview',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Starts a batch import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import.
   */
  protected function batchImport(FeedInterface $feed) {
    $this->drupalGet('feed/' . $feed->id() . '/import');
    $this->submitForm([], 'Import');
  }

  /**
   * Asserts number of files in the feeds "in progress" dir.
   *
   * @param int $count
   *   The expected number of files.
   * @param string $subdirectory
   *   (optional) The directory to look into within the "in progress" dir.
   * @param string $stream
   *   (optional) The stream to use: 'public' or 'private'. Defaults to
   *   'private'.
   */
  protected function assertCountFilesInProgressDir(int $count, string $subdirectory = '', string $stream = 'private') {
    // Assert that a file exists in the in_progress dir.
    $dir = $stream . '://feeds/in_progress';
    if ($subdirectory) {
      $dir .= '/' . $subdirectory;
    }
    $files = $this->container->get('file_system')->scanDirectory($dir, '/.*/');
    $this->assertCount($count, $files);
  }

}
