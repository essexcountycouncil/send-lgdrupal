<?php

namespace Drupal\Tests\localgov_geo\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Geo ovewview page access tests.
 *
 * Ensures that non-admin users with the right permissions can access the Geo
 * overview page.
 *
 * @group localgov_geo
 */
class GeoOverviewAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'localgov_geo',
  ];

  /**
   * Permissions for the admin user that will be logged-in for test.
   *
   * @var array
   */
  protected static $adminUserPermissions = [
    'access geo overview',
    'delete geo',
    'create geo',
    'edit geo',
    'administer geo types',
  ];

  /**
   * Permissions given to an Editor user.
   *
   * @var array
   */
  protected static $nonAdminUserPermissions = [
    'access geo overview',
    'create geo',
    'edit geo',
    'delete geo',
  ];

  /**
   * Permissions for non-editor who can create geo.
   *
   * @var array
   */
  protected static $creatorUserPermissions = [
    'create geo',
    'edit geo',
  ];

  /**
   * An admin test user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A non-admin test user account.
   *
   * @var \drupal\user\userinterface
   */
  protected $nonAdminUser;

  /**
   * A creator test user account.
   *
   * @var \drupal\user\userinterface
   */
  protected $creatorUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Have two users ready to be used in tests.
    $this->adminUser = $this->drupalCreateUser(static::$adminUserPermissions);
    $this->nonAdminUser = $this->drupalCreateUser(static::$nonAdminUserPermissions);
    $this->creatorUser = $this->drupalCreateUser(static::$creatorUserPermissions);

    // Start off logged in as admin.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Access test for the Geo overview page.
   *
   * The Editor user should be able to access the overview page.
   */
  public function testOverviewPageAccess() {

    $this->drupalGet('/admin/content/geo');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/admin/content/geo');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->creatorUser);
    $this->drupalGet('/admin/content/geo');
    $this->assertSession()->statusCodeEquals(403);
  }

}
