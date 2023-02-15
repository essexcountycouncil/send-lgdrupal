<?php

namespace Drupal\Tests\localgov_paragraphs\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Validates Contact Page component edit form.
 *
 * We should be able to edit Contact Page components.
 */
class ContactEditTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_paragraphs',
    'paragraphs_library',
  ];

  /**
   * {@inheritdoc}
   *
   * Claro is throwing the exception.
   */
  protected $defaultTheme = 'claro';

  /**
   * Skip schema check for third_party_settings config from media_library_edit.
   *
   * See https://www.drupal.org/project/media_library_edit/issues/3315757.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = [
    'core.entity_form_display.paragraph.localgov_featured_campaign.default',
    'core.entity_form_display.paragraph.localgov_image.default',
  ];

  /**
   * {@inheritdoc}
   *
   * Create a Contact page component.
   */
  protected function setUp(): void {
    parent::setUp();

    $paragraph_storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $contact_paragraph = $paragraph_storage->create([
      'type' => 'localgov_contact',
      'localgov_contact_heading' => 'Foo',
    ]);
    $contact_paragraph->save();

    $page_component_storage = $this->container->get('entity_type.manager')->getStorage('paragraphs_library_item');
    $contact_page_component = $page_component_storage->create([
      'label' => 'Foo',
      'paragraphs' => $contact_paragraph,
    ]);
    $contact_page_component->save();

    $this->drupalLogin($this->drupalCreateUser(['administer paragraphs library']));
  }

  /**
   * Ensure we can edit Contact page components.
   */
  public function testContactPageComponentEditPage(): void {

    $this->drupalGet('admin/content/paragraphs/1/edit');
    $this->assertSession()->elementExists('css', '#edit-label-0-value');
  }

}
