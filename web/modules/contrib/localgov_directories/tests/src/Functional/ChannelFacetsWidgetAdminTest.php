<?php

namespace Drupal\Tests\localgov_directories\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the configuration of channel widget.
 *
 * @group localgov_directories
 */
class ChannelFacetsWidgetAdminTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;

  /**
   * A user with mininum permissions for test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_directories',
    'field_ui',
    'block',
  ];


  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    for ($j = 1; $j < 3; $j++) {
      $directory = $this->createNode([
        'title' => 'Directory ' . $j,
        'type' => 'localgov_directory',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_facets_enable' => [],
      ]);
      $directory->save();
      $this->directories[$j] = $directory;
    }

    // Content type configured to reference directories, and have the
    // facet selector.
    $this->createContentType(['type' => 'entry_1']);
    $this->createContentType(['type' => 'entry_2']);

    // Configure directory channels to allow different entry types.
    $this->directories[1]->localgov_directory_channel_types = [
      'target_id' => 'entry_1',
    ];
    $this->directories[1]->save();
    $this->directories[2]->localgov_directory_channel_types = [
      ['target_id' => 'entry_1'],
      ['target_id' => 'entry_2'],
    ];
    $this->directories[2]->save();

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test selecting channels and facets appearing.
   */
  public function testDirectoryChannelWidget() {
    // Create the fields with the selector.
    $this->fieldUIAddNewField(
      'admin/structure/types/manage/entry_1',
      'channels',
      'Channels',
      'field_ui:entity_reference:node',
      [],
      [
        'settings[handler]' => 'localgov_directories_channels_selection',
        // No javascript update; and fieldUIAddNewField is too fast for it with.
        'settings[handler_settings][target_bundles][localgov_directory]' => TRUE,
      ]
    );
    $this->fieldUIAddExistingField(
      'admin/structure/types/manage/entry_2',
      'field_channels',
      'Channels',
      [
        'settings[handler]' => 'localgov_directories_channels_selection',
        // No javascript update; and fieldUIAddNewField is too fast for it with.
        'settings[handler_settings][target_bundles][localgov_directory]' => TRUE,
      ]
    );
    // Set the widget.
    $this->drupalGet('/admin/structure/types/manage/entry_1/form-display');
    $this->submitForm(['fields[field_channels][type]' => 'localgov_directories_channel_selector'], 'edit-submit');
    $this->drupalGet('/admin/structure/types/manage/entry_2/form-display');
    $this->submitForm(['fields[field_channels][type]' => 'localgov_directories_channel_selector'], 'edit-submit');

    // Check the correct channels are on the different entry forms.
    $this->drupalGet('/node/add/entry_1');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Directory 1');
    $assert_session->pageTextContains('Directory 2');
    $this->drupalGet('/node/add/entry_2');
    $assert_session->pageTextNotContains('Directory 1');
    $assert_session->pageTextContains('Directory 2');

    // Set a default.
    $this->drupalGet('/admin/structure/types/manage/entry_1/fields/node.entry_1.field_channels');
    $this->submitForm(
      ['default_value_input[field_channels][primary]' => $this->directories[2]->id()],
      'edit-submit'
    );
    $this->drupalGet('/admin/structure/types/manage/entry_2/fields/node.entry_2.field_channels');
    $this->submitForm(
      ['default_value_input[field_channels][primary]' => $this->directories[2]->id()],
      'edit-submit'
    );

    // Check default applied.
    $this->drupalGet('/node/add/entry_1');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $radio = $page->findField('field_channels[primary]');
    $this->assertEquals($radio->getValue(), $this->directories[2]->id());
    $this->drupalGet('/node/add/entry_2');
    $radio = $page->findField('field_channels[primary]');
    $this->assertEquals($radio->getValue(), $this->directories[2]->id());
    $assert_session->fieldNotExists('edit-field-channels-secondary-1');
  }

}
