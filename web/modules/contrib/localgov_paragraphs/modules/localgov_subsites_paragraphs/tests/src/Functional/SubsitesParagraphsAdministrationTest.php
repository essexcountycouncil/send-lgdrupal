<?php

namespace Drupal\Tests\localgov_subsites_paragraphs\Functional;

use Drupal\Tests\paragraphs\Functional\WidgetLegacy\ParagraphsTestBase;

/**
 * Tests the configuration of localgov_paragraphs.
 */
class SubsitesParagraphsAdministrationTest extends ParagraphsTestBase {

  /**
   * Skip schema check for third_party_settings config from media_library_edit.
   *
   * See https://www.drupal.org/project/media_library_edit/issues/3315757.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = [
    'core.entity_form_display.paragraph.localgov_accordion.default',
    'core.entity_form_display.paragraph.localgov_accordion_pane.default',
    'core.entity_form_display.paragraph.localgov_banner_primary.default',
    'core.entity_form_display.paragraph.localgov_banner_secondary.default',
    'core.entity_form_display.paragraph.localgov_block_view.default',
    'core.entity_form_display.paragraph.localgov_box_link.default',
    'core.entity_form_display.paragraph.localgov_box_links_listing.default',
    'core.entity_form_display.paragraph.localgov_call_out_box.default',
    'core.entity_form_display.paragraph.localgov_documents.default',
    'core.entity_form_display.paragraph.localgov_fact_box.default',
    'core.entity_form_display.paragraph.localgov_featured_subsite.default',
    'core.entity_form_display.paragraph.localgov_featured_teaser.default',
    'core.entity_form_display.paragraph.localgov_featured_teasers.default',
    'core.entity_form_display.paragraph.localgov_key_contact_item.default',
    'core.entity_form_display.paragraph.localgov_key_contacts.default',
    'core.entity_form_display.paragraph.localgov_key_fact.default',
    'core.entity_form_display.paragraph.localgov_key_facts.default',
    'core.entity_form_display.paragraph.localgov_link_and_summary.default',
    'core.entity_form_display.paragraph.localgov_media_with_text.default',
    'core.entity_form_display.paragraph.localgov_quote.default',
    'core.entity_form_display.paragraph.localgov_table.default',
    'core.entity_form_display.paragraph.localgov_tab_panel.default',
    'core.entity_form_display.paragraph.localgov_tabs.default',
    'core.entity_form_display.paragraph.localgov_video.default',
    'core.entity_form_display.paragraph.localgov_contact.default',
    'core.entity_form_display.paragraph.localgov_image.default',
    'core.entity_form_display.paragraph.localgov_link.default',
    'core.entity_form_display.paragraph.localgov_text.default',
    'core.entity_form_display.paragraph.localgov_featured_campaign.default',
    'core.entity_form_display.paragraph.localgov_ia_block.default',
    'core.entity_form_display.paragraph.localgov_labelled_icon.default',
    'core.entity_form_display.paragraph.localgov_newsroom_teaser.default',
    'core.entity_form_display.paragraph.localgov_subscribe_panel.default',
    'core.entity_view_display.paragraph.localgov_featured_campaign.default',
    'core.entity_view_display.paragraph.localgov_ia_block.default',
    'core.entity_view_display.paragraph.localgov_labelled_icon.default',
    'core.entity_view_display.paragraph.localgov_newsroom_teaser.default',
    'core.entity_view_display.paragraph.localgov_newsroom_teaser.featured_large',
    'core.entity_view_display.paragraph.localgov_newsroom_teaser.featured',
    'core.entity_view_display.paragraph.localgov_subscribe_panel.default',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'localgov_subsites_paragraphs',
  ];

  /**
   * Tests the LocalGovDrupal core paragraph types.
   */
  public function testSubsiteParagraphsTypes() {
    $this->loginAsAdmin([
      'administer paragraphs types',
    ]);

    // Check paragraph types installed.
    $this->drupalGet('/admin/structure/paragraphs_type');
    $this->assertSession()->pageTextContains('localgov_accordion');
    $this->assertSession()->pageTextContains('localgov_accordion_pane');
    $this->assertSession()->pageTextContains('localgov_box_link');
    $this->assertSession()->pageTextContains('localgov_call_out_box');
    $this->assertSession()->pageTextContains('localgov_documents');
    $this->assertSession()->pageTextContains('localgov_fact_box');
    $this->assertSession()->pageTextContains('localgov_link_and_summary');
    $this->assertSession()->pageTextContains('localgov_quote');
    $this->assertSession()->pageTextContains('localgov_table');
    $this->assertSession()->pageTextContains('localgov_video');

    // Check 'Accordion' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_accordion/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_paragraphs');

    // Check 'Accordion pane' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_accordion_pane/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_body_text');

    // Check 'Block view' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_block_view/fields');
    $this->assertSession()->pageTextContains('localgov_embed_block_view');

    // Check 'Box link' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_box_link/fields');
    $this->assertSession()->pageTextContains('localgov_image');
    $this->assertSession()->pageTextContains('localgov_link');
    $this->assertSession()->pageTextContains('localgov_opens_in_a_new_window');

    // Check 'Box links listing' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_box_links_listing/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_paragraphs');
    $this->assertSession()->pageTextContains('localgov_box_listing_theme');

    // Check 'Call out box' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_call_out_box/fields');
    $this->assertSession()->pageTextContains('localgov_background_image');
    $this->assertSession()->pageTextContains('localgov_body_text');
    $this->assertSession()->pageTextContains('localgov_button');
    $this->assertSession()->pageTextContains('localgov_header_text');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_opens_in_a_new_window');
    $this->assertSession()->pageTextContains('localgov_colour_theme');

    // Check 'Documents' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_documents/fields');
    $this->assertSession()->pageTextContains('localgov_documents');

    // Check 'Fact box' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_fact_box/fields');
    $this->assertSession()->pageTextContains('localgov_above_text');
    $this->assertSession()->pageTextContains('localgov_background');
    $this->assertSession()->pageTextContains('localgov_below_text');
    $this->assertSession()->pageTextContains('localgov_fact');

    // Check 'Featured teasers' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_featured_teasers/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_paragraphs');

    // Check 'Featured teaser' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_featured_teaser/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_image');
    $this->assertSession()->pageTextContains('localgov_text');
    $this->assertSession()->pageTextContains('localgov_link');

    // Check 'Key contacts' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_key_contacts/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_paragraphs');

    // Check 'Key contact item' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_key_contact_item/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_colour_theme');
    $this->assertSession()->pageTextContains('localgov_image');
    $this->assertSession()->pageTextContains('localgov_link');
    $this->assertSession()->pageTextContains('localgov_contact_email');

    // Check 'Key facts' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_key_facts/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_paragraphs');

    // Check 'Key fact' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_key_fact/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_text');

    // Check 'Media with text' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_media_with_text/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_text');
    $this->assertSession()->pageTextContains('localgov_link');
    $this->assertSession()->pageTextContains('localgov_media_item');
    $this->assertSession()->pageTextContains('localgov_media_position');
    $this->assertSession()->pageTextContains('localgov_media_with_text_style');
    $this->assertSession()->pageTextContains('localgov_opens_in_a_new_window');

    // Check 'Link and summary' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_link_and_summary/fields');
    $this->assertSession()->pageTextContains('localgov_summary');
    $this->assertSession()->pageTextContains('localgov_link');

    // Check 'Quote' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_quote/fields');
    $this->assertSession()->pageTextContains('localgov_author');
    $this->assertSession()->pageTextContains('localgov_text_plain');

    // Check 'Table' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_table/fields');
    $this->assertSession()->pageTextContains('localgov_table');
    $this->assertSession()->pageTextContains('localgov_table_theme');

    // Check 'Tabs' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_tabs/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_paragraphs');

    // Check 'Tab panel' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_tab_panel/fields');
    $this->assertSession()->pageTextContains('localgov_title');
    $this->assertSession()->pageTextContains('localgov_heading_level');
    $this->assertSession()->pageTextContains('localgov_body_text');

    // Check 'Video' fields.
    $this->drupalGet('/admin/structure/paragraphs_type/localgov_video/fields');
    $this->assertSession()->pageTextContains('localgov_video');
  }

}
