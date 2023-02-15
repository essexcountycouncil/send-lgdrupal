<?php

namespace Drupal\Tests\localgov_directories\FunctionalJavascript;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\node\NodeInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the output of facet and channel widgets on directory entries.
 *
 * @group localgov_directories
 */
class EntityReferenceFacetsWidgetTest extends WebDriverTestBase {

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
  ];


  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Three bundles of five facets.
    for ($i = 0; $i < 3; $i++) {
      $type_id = $this->randomMachineName();
      $type = LocalgovDirectoriesFacetsType::create([
        'id' => $type_id,
        'label' => $type_id,
      ]);
      $type->save();
      $this->facet_types[$type_id] = $type;
      for ($j = 0; $j < 5; $j++) {
        $facet = LocalgovDirectoriesFacets::create([
          'bundle' => $type_id,
          'title' => $this->randomMachineName(),
        ]);
        $facet->save();
        $this->facets[$type_id][$facet->id()] = $facet;
      }
    }
    // Another bundle with just one facet.
    $type = LocalgovDirectoriesFacetsType::create([
      'id' => 'facetbundleonefacet',
      'label' => 'facetbundleonefacet',
    ]);
    $type->save();
    $this->facet_types['facetbundleonefacet'] = $type;
    $facet = LocalgovDirectoriesFacets::create([
      'bundle' => 'facetbundleonefacet',
      'title' => $this->randomMachineName(),
    ]);
    $facet->save();
    $this->facets['facetbundleonefacet'][$facet->id()] = $facet;

    // Directory 1
    // Single facet bundle.
    reset($this->facet_types);
    $directory = $this->createNode([
      'title' => 'Directory 1',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
      'localgov_directory_facets_enable' => [
        ['target_id' => key($this->facet_types)],
      ],
    ]);
    $directory->save();
    $this->directories['single_facet'] = $directory;

    // Directory 2.
    // All facet bundles.
    $all_enabled = [];
    foreach ($this->facet_types as $type_id => $type) {
      $all_enabled[] = ['target_id' => $type_id];
    }
    $directory = $this->createNode([
      'title' => 'Directory 2',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
      'localgov_directory_facets_enable' => $all_enabled,
    ]);
    $directory->save();
    $this->directories['all_facets'] = $directory;

    // Directory 3.
    // Single facet with only one value.
    $directory = $this->createNode([
      'title' => 'Directory 3',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
      'localgov_directory_facets_enable' => [
        ['target_id' => 'facetbundleonefacet'],
      ],
    ]);
    $directory->save();
    $this->directories['single_facet_single_value'] = $directory;

    // Directory 4.
    // No facets.
    $directory = $this->createNode([
      'title' => 'Directory 4',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
      'localgov_directory_facets_enable' => [],
    ]);
    $directory->save();
    $this->directories['no_facets'] = $directory;

    // Content type configured to reference directories, and have the
    // facet selector.
    $this->createContentType(['type' => 'entry']);
    $display_repository = \Drupal::service('entity_display.repository');
    assert($display_repository instanceof EntityDisplayRepositoryInterface);
    // Add the directory entity reference and facets fields.
    $field_name = 'localgov_directory_facets_select';
    $this->createEntityReferenceField(
      'node',
      'entry',
      $field_name,
      $field_name,
      'localgov_directories_facets',
      'localgov_directories_facets_selection'
    );
    $form_display = $display_repository->getFormDisplay('node', 'entry');
    $form_display->setComponent($field_name, [
      'type' => 'localgov_directories_facet_checkbox',
    ]);
    $form_display->save();
    $field_name = 'localgov_directory_channels';
    $this->createEntityReferenceField(
      'node',
      'entry',
      $field_name,
      $field_name,
      'node',
      'localgov_directories_channels_selection'
    );
    $form_display = $display_repository->getFormDisplay('node', 'entry');
    $form_display->setComponent($field_name, [
      'type' => 'localgov_directories_channel_selector',
    ]);
    $form_display->save();

    // User that can create entries.
    $this->user = $this->drupalCreateUser([
      'access content',
      'bypass node access',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test selecting channels and facets appearing.
   */
  public function testDirectoryChannelWidget() {
    // No channels selected. Message to select in the facets area.
    $this->drupalGet('node/add/entry');
    $entry = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Select directory channels to add facets');

    // Select a channel.
    $radio = $entry->findField('edit-localgov-directory-channels-primary-' . $this->directories['single_facet']->id());
    $radio->click();
    $assert_session->assertWaitOnAjaxRequest();
    $facet_type_enabled = $this->directories['single_facet']->localgov_directory_facets_enable->first()->target_id;
    // Check facets appear.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-localgov-directory-facets-select-' . strtolower($facet_type_enabled) . '"]');
    foreach ($this->facets[$facet_type_enabled] as $facet) {
      $assert_session->fieldExists($facet->label());
    }
    next($this->facet_types);
    // But not others.
    $facet_not_enabled = key($this->facet_types);
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-localgov-directory-facets-select-' . $facet_not_enabled . '"]');

    // Enable other channel.
    $radio = $entry->findField('edit-localgov-directory-channels-secondary-' . $this->directories['all_facets']->id());
    $radio->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Check all facets appear.
    // Select them, and submit.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-localgov-directory-facets-select-' . strtolower($facet_not_enabled) . '"]');
    foreach ($this->facets as $facet_types) {
      foreach ($facet_types as $facet) {
        $assert_session->fieldExists($facet->label());
        $current_facet = $entry->findField($facet->label());
        $current_facet->click();
      }
    }
    $title = $this->randomString();
    $entry->fillField('title[0][value]', $title);
    $entry->pressButton('edit-submit');
    // Ensure it's posted.
    $assert_session->pageTextContains('entry ' . $title . ' has been created.');
    $this->drupalGet('node/5/edit');
    $entry = $this->getSession()->getPage();
    // Ensure the channel and facets are selected.
    $radio = $entry->findField('edit-localgov-directory-channels-secondary-' . $this->directories['all_facets']->id());
    $this->assertTrue($radio->isChecked());
    $assert_session->elementExists('css', '[data-drupal-selector="edit-localgov-directory-facets-select-' . strtolower($facet_not_enabled) . '"]');
    foreach ($this->facets as $facet_types) {
      foreach ($facet_types as $facet) {
        $current_facet = $entry->findField($facet->label());
        $this->assertTrue($current_facet->isChecked());
      }
    }

    // Enable a channel with no facets.
    // Ensure it saves.
    $this->drupalGet('node/add/entry');
    $entry = $this->getSession()->getPage();
    $radio = $entry->findField('edit-localgov-directory-channels-secondary-' . $this->directories['no_facets']->id());
    $radio->click();
    $assert_session->assertWaitOnAjaxRequest();
    $title = $this->randomString();
    $entry->fillField('title[0][value]', $title);
    $entry->pressButton('edit-submit');
    // Ensure it's posted.
    $assert_session->pageTextContains('entry ' . $title . ' has been created.');
  }

  /**
   * Test if the facet field is required.
   */
  public function testFacetRequired() {
    // Set the field to be required.
    $facet_field = FieldConfig::loadByName('node', 'entry', 'localgov_directory_facets_select');
    $facet_field->set('required', TRUE);
    $facet_field->save();
    $this->resetAll();
    $facet_field = FieldConfig::loadByName('node', 'entry', 'localgov_directory_facets_select');
    $this->assertTrue($facet_field->isRequired());

    $this->drupalGet('node/add/entry');
    $entry = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Select the single value facet channel.
    // And check it is default selected as the only option on a required
    // field.
    $radio = $entry->findField('edit-localgov-directory-channels-primary-' . $this->directories['single_facet_single_value']->id());
    $radio->click();
    $assert_session->assertWaitOnAjaxRequest();
    $facet_type_enabled = $this->directories['single_facet_single_value']->localgov_directory_facets_enable->first()->target_id;
    $assert_session->elementExists('css', '[data-drupal-selector="edit-localgov-directory-facets-select-' . strtolower($facet_type_enabled) . '"]');
    foreach ($this->facets['facetbundleonefacet'] as $facet) {
      $assert_session->fieldExists($facet->label());
      $single_facet = $entry->findField($facet->label());
      $this->assertTrue($single_facet->isChecked());
    }
    $title = $this->randomString();
    $entry->fillField('title[0][value]', $title);
    $entry->pressButton('edit-submit');
    $assert_session->pageTextContains('entry ' . $title . ' has been created.');

    // Select a multiple value facet channel.
    // Check that a field must be filled in.
    $this->drupalGet('node/add/entry');
    $entry = $this->getSession()->getPage();
    $radio = $entry->findField('edit-localgov-directory-channels-primary-' . $this->directories['all_facets']->id());
    $radio->click();
    $assert_session->assertWaitOnAjaxRequest();
    $title = $this->randomString();
    $entry->fillField('title[0][value]', $title);
    $entry->pressButton('edit-submit');
    $assert_session->pageTextContains('localgov_directory_facets_select field is required.');
  }

}
