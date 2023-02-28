<?php

namespace Drupal\Tests\localgov_paragraphs\Functional;

use Drupal\Tests\paragraphs\Functional\WidgetLegacy\ParagraphsTestBase;

/**
 * Tests the configuration of localgov_paragraphs.
 */
class ParagraphsAdministrationTest extends ParagraphsTestBase {

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
    'core.entity_form_display.paragraph.localgov_newsroom_teaser.default',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'localgov_paragraphs',
  ];

  /**
   * Tests the LocalGovDrupal core paragraph types.
   */
  public function testParagraphsTypes() {
    $this->loginAsAdmin([
      'administer paragraphs types',
    ]);

    // Check paragraph types installed.
    $this->drupalGet('/admin/structure/paragraphs_type');
    $this->assertSession()->pageTextContains('localgov_contact');
    $this->assertSession()->pageTextContains('localgov_image');
    $this->assertSession()->pageTextContains('localgov_link');
    $this->assertSession()->pageTextContains('localgov_text');

    // Check advanced_links paragraph fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_link/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_url');

    // Check contact paragraph fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_contact/fields');
    $this->assertSession()->pageTextContains('localgov_contact_address');
    $this->assertSession()->pageTextContains('localgov_contact_url');
    $this->assertSession()->pageTextContains('localgov_contact_email');
    $this->assertSession()->pageTextContains('localgov_contact_facebook');
    $this->assertSession()->pageTextContains('localgov_contact_heading');
    $this->assertSession()->pageTextContains('localgov_contact_instagram');
    $this->assertSession()->pageTextContains('localgov_contact_location');
    $this->assertSession()->pageTextContains('localgov_contact_minicom');
    $this->assertSession()->pageTextContains('localgov_contact_mobile');
    $this->assertSession()->pageTextContains('localgov_contact_office_hours');
    $this->assertSession()->pageTextContains('localgov_contact_other_social');
    $this->assertSession()->pageTextContains('localgov_contact_other_url');
    $this->assertSession()->pageTextContains('localgov_contact_out_of_hours');
    $this->assertSession()->pageTextContains('localgov_contact_phone');
    $this->assertSession()->pageTextContains('localgov_contact_subheading');
    $this->assertSession()->pageTextContains('localgov_contact_twitter');

    // Check pagebuilder_image paragraph fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_image/fields');
    $this->assertSession()->pageTextContains('localgov_caption');
    $this->assertSession()->pageTextContains('localgov_image');

    // Check pagebuilder_text paragraph fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_text/fields');
    $this->assertSession()->pageTextContains('localgov_text');
  }

  /**
   * Tests the paragraph creation.
   */
  public function testParagraphsCreation() {
    // Create an node type with paragraphs field.
    $this->addParagraphedContentType('paragraphs', 'field_paragraphs', 'entity_reference_paragraphs');
    $this->loginAsAdmin([
      'administer site configuration',
      'create paragraphs content',
      'administer nodes',
    ]);

    // Add a new paragraph node with a text paragraph field.
    $this->drupalGet('node/add/paragraphs');
    $this->submitForm([], 'field_paragraphs_localgov_text_add_more');
    $edit = [
      'title[0][value]' => 'Test',
      'field_paragraphs[0][subform][localgov_text][0][value]' => 'Test paragraph text',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('has been created');
    $this->assertSession()->pageTextContains('Test paragraph text');
  }

}
