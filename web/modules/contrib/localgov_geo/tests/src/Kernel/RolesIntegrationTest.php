<?php

namespace Drupal\Tests\localgov_geo\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_roles\RolesHelper;
use Drupal\user\Entity\Role;

/**
 * Tests default roles.
 *
 * @group localgov_geo
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
    'field',
    'text',
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
    $this->container->get('module_installer')->install(['localgov_geo']);

    $editor = Role::load(RolesHelper::EDITOR_ROLE);
    $author = Role::load(RolesHelper::AUTHOR_ROLE);
    $permissions = [
      'access geo overview' =>
        ['editor' => TRUE, 'author' => FALSE],
      'create geo' =>
        ['editor' => TRUE, 'author' => TRUE],
      'delete geo' =>
        ['editor' => TRUE, 'author' => FALSE],
      'edit geo' =>
        ['editor' => TRUE, 'author' => FALSE],
    ];

    foreach ($permissions as $permission => $grant) {
      $this->assertEquals($author->hasPermission($permission), $grant['author']);
      $this->assertEquals($editor->hasPermission($permission), $grant['editor']);
    }
  }

}
