<?php

namespace Drupal\Tests\localgov_directories\Kernel;

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
    $this->container->get('module_installer')->install(['localgov_directories']);

    $editor = Role::load(RolesHelper::EDITOR_ROLE);
    $author = Role::load(RolesHelper::AUTHOR_ROLE);
    $contributor = Role::load(RolesHelper::CONTRIBUTOR_ROLE);
    $permissions = [
      'access directory facets overview',
      'delete directory facets',
      'create directory facets',
      'view directory facets',
      'edit directory facets',
      'create localgov_directory content',
      'delete any localgov_directory content',
      'delete own localgov_directory content',
      'edit any localgov_directory content',
      'edit own localgov_directory content',
      'revert localgov_directory revisions',
      'view localgov_directory revisions',
    ];

    foreach ($permissions as $permission) {
      $this->assertFalse($author->hasPermission($permission));
      $this->assertFalse($contributor->hasPermission($permission));
      $this->assertTrue($editor->hasPermission($permission));
    }
  }

}
