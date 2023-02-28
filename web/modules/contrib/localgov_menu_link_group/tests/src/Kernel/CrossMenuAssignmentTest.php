<?php

namespace Drupal\Tests\localgov_menu_link_group\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\user\Entity\User;
use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_menu_link_group\Entity\LocalGovMenuLinkGroup;
use Drupal\localgov_menu_link_group\Form\LocalGovMenuLinkGroupForm;

/**
 * Tests for the Entity form.
 *
 * Tests for cases where the menu of the selected parent menu link is different
 * from the menu of the selected child menu links.
 *
 * @group localgov_menu_link_group
 */
class CrossMenuAssignmentTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'localgov_menu_link_group'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->installConfig(self::$modules);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('localgov_menu_link_group');
    $this->container->get('plugin.manager.menu.link')->rebuild();

    $admin_user = User::create([
      'name' => 'admin',
      'mail' => 'admin@example.net',
    ]);
    $admin_user->addRole('administrator');
    $admin_user->save();
    $this->container->get('current_user')->setAccount($admin_user);
  }

  /**
   * Test the entity form for cross menu selections.
   *
   * - Create a new group entity through form submission.  Use different
   *   **menus** for parent and child links.  For example, "account" menu for
   *   the parent menu link and "admin" menu for child menu links.
   * - Load the newly created group entity and check the parent menu, parent
   *   menu link and child menu links.
   * - The menu name for the parent menu link should be used as the parent menu
   *   of the group.  The menu name of the child menu link should be ignored.
   * - The menu name should **not** be present in parent menu link and child
   *   menu links.
   */
  public function testFormSubmission() {

    $empty_group = LocalGovMenuLinkGroup::create();
    $create_form_obj = LocalGovMenuLinkGroupForm::create($this->container);
    $create_form_obj->setEntity($empty_group);
    $create_form_obj->setModuleHandler($this->container->get('module_handler'));
    $create_form_obj->setEntityTypeManager($this->container->get('entity_type.manager'));
    $create_form_state = new FormState();
    $create_form_state->setValue('id', $group_id = 'foo');
    $create_form_state->setValue('group_label', $group_label = 'Foo');
    $create_form_state->setValue('parent_menu_link', 'account:user.page');
    $create_form_state->setValue('child_menu_links', ['admin:system.admin_content']);
    $create_form_state->setValue('op', 'Save');
    $this->container->get('form_builder')->submitForm($create_form_obj, $create_form_state);

    $this->assertEmpty($create_form_state->getErrors());

    $new_group = LocalGovMenuLinkGroup::load(LocalGovMenuLinkGroupForm::ENTITY_ID_PREFIX . $group_id);
    $expected_group_label = $group_label;
    $this->assertEquals($expected_group_label, $new_group->label());

    $parent_menu_name = $new_group->get('parent_menu');
    $expected_parent_menu_name = 'account';
    $this->assertEquals($parent_menu_name, $expected_parent_menu_name);

    $parent_menu_link = $new_group->get('parent_menu_link');
    $expected_parent_menu_link = 'user.page';
    $this->assertEquals($parent_menu_link, $expected_parent_menu_link);

    $child_menu_links = $new_group->get('child_menu_links');
    $expected_child_menu_links = ['system.admin_content'];
    $this->assertEquals($child_menu_links, $expected_child_menu_links);
  }

}
