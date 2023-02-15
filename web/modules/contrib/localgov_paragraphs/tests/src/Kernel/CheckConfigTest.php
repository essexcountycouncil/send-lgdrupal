<?php

namespace Drupal\Tests\localgov_paragraphs\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group localgov_paragraphs
 */
class CheckConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'address',
    'entity_reference_revisions',
    'field_formatter_class',
    'field_group',
    'file',
    'fontawesome',
    'geolocation',
    'geolocation_google_maps',
    'layout_discovery',
    'layout_paragraphs',
    'link',
    'media',
    'media_library',
    'node',
    'office_hours',
    'options',
    'paragraphs',
    'paragraphs_library',
    'tablefield',
    'telephone',
    'views',
    'viewsreference',
    'localgov_paragraphs',
    'localgov_paragraphs_layout',
    'localgov_paragraphs_views',
    'localgov_subsites_paragraphs',
    'localgov_homepage_paragraphs',
  ];

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
   * Tests that the module's config installs properly.
   */
  public function testConfig() {

    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('paragraph');
    $this->installConfig([
      'localgov_paragraphs',
      'localgov_paragraphs_layout',
      'localgov_subsites_paragraphs',
      'localgov_paragraphs_views',
      'localgov_homepage_paragraphs',
    ]);
  }

}
