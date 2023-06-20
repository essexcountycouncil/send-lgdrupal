<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\user\Entity\Role;

/**
 * Tests related to permissions used in Feeds.
 *
 * @group feeds
 */
class FeedsPermissionsTest extends FeedsKernelTestBase {

  /**
   * Tests updating a role with permission for feed type which no longer exists.
   */
  public function testPermissionDependency() {
    // Add a feed type.
    $feed_type = $this->createFeedType([
      'id' => 'foo',
    ]);

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
    $role->grantPermission('view foo feeds');
    $role->save();

    // Now delete the feed type.
    $feed_type->delete();

    // Save again, and verify the correct role saving.
    $role = $this->reloadEntity($role);
    $this->assertSame(SAVED_UPDATED, $role->save());
  }

}
