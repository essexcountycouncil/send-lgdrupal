<?php

namespace Drupal\Tests\localgov_directories_promo_page\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_roles\RolesHelper;
use Drupal\user\Entity\Role;

/**
 * Tests default roles.
 *
 * @group localgov_directories
 */
class RolesIntegrationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'localgov_roles',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user', 'localgov_roles']);
  }

  /**
   * Check default roles applied.
   */
  public function testEnablingRolesModule() {
    $this->container->get('module_installer')->install(['localgov_directories_promo_page']);

    $editor = Role::load(RolesHelper::EDITOR_ROLE);
    $author = Role::load(RolesHelper::AUTHOR_ROLE);
    $contributor = Role::load(RolesHelper::CONTRIBUTOR_ROLE);
    $permissions = [
      'create localgov_directory_promo_page content' =>
        ['editor' => TRUE, 'author' => TRUE, 'contributor' => TRUE],
      'delete any localgov_directory_promo_page content' =>
        ['editor' => TRUE, 'author' => FALSE, 'contributor' => FALSE],
      'delete own localgov_directory_promo_page content' =>
        ['editor' => TRUE, 'author' => TRUE, 'contributor' => TRUE],
      'edit any localgov_directory_promo_page content' =>
        ['editor' => TRUE, 'author' => FALSE, 'contributor' => FALSE],
      'edit own localgov_directory_promo_page content' =>
        ['editor' => TRUE, 'author' => TRUE, 'contributor' => TRUE],
      'revert localgov_directory_promo_page revisions' =>
        ['editor' => TRUE, 'author' => TRUE, 'contributor' => FALSE],
      'view localgov_directory_promo_page revisions' =>
        ['editor' => TRUE, 'author' => TRUE, 'contributor' => TRUE],
    ];

    foreach ($permissions as $permission => $grant) {
      $this->assertEquals($author->hasPermission($permission), $grant['author']);
      $this->assertEquals($contributor->hasPermission($permission), $grant['contributor']);
      $this->assertEquals($editor->hasPermission($permission), $grant['editor']);
    }
  }

}
