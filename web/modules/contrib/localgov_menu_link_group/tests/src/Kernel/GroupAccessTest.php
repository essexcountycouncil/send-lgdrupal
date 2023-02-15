<?php

namespace Drupal\Tests\localgov_menu_link_group\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Html;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Menu link group access test.
 *
 * When a Menu link group has no children, it should not be rendered.
 *
 * @group localgov_menu_link_group
 */
class GroupAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'toolbar',
    'localgov_menu_link_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
  }

  /**
   * Test that the "Test" group is present.
   *
   * - Users with the 'administer site configuration' permission should see this
   *   Menu link group.
   */
  public function testGroupAccess() {

    $this->container->get('module_installer')->install(['group_config_test']);

    // Admin user should see the "Test" Menu link group.
    $user_a = $this->createUser([
      'access administration pages',
      'access toolbar',
      'administer site configuration',
    ]);
    $this->container->get('account_switcher')->switchTo($user_a);

    $toolbar = toolbar_get_rendered_subtrees();
    $rendered_config_menu_markup = (string) $toolbar[0]['system-admin_config'];
    $dom = Html::load($rendered_config_menu_markup);
    $xpath = new \DomXPath($dom);

    $has_test_group = ($xpath->query('//span[text()="Test"]')->count() === 1);
    $this->assertTrue($has_test_group);

    // Non-admin user should not see the "Test" Menu link group.
    $user_b = $this->createUser([
      'access administration pages',
      'access toolbar',
    ]);
    $this->container->get('account_switcher')->switchTo($user_b);

    $toolbar = toolbar_get_rendered_subtrees();
    $rendered_config_menu_markup = (string) $toolbar[0]['system-admin_config'];
    $dom = Html::load($rendered_config_menu_markup);
    $xpath = new \DomXPath($dom);

    $has_test_group = ($xpath->query('//span[text()="Test"]')->count() === 1);
    $this->assertFalse($has_test_group);
  }

}
