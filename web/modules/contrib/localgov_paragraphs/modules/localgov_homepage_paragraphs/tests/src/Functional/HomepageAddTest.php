<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_homepage_paragraphs\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional test for Homepage creation.
 *
 * Fill in the form for the LocalGov Dummy Homepage content type and save.  This
 * is a content type created out of the Paragraphs used to prepare the real
 * homepage.
 *
 * @group localgov_homepage_paragraphs
 */
class HomepageAddTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['localgov_homepage_paragraphs_dummy_content_type'];

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
   * Test homepage creation.
   */
  public function testCreateHomepage() {

    $account = $this->drupalCreateUser([
      'create localgov_dummy_homepage content',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('node/add/localgov_dummy_homepage');
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();

    $page->fillField('title[0][value]', 'I am a dummy LocalGov homepage :)');

    // CTAs i.e. Icon collection.
    $page->fillField('localgov_homepage_labelled_icons[0][subform][localgov_labelled_icon_title][0][value]', 'Drupal');
    $page->fillField('localgov_homepage_labelled_icons[0][subform][localgov_labelled_icon_icon][0][icon_name]', 'drupal');

    // Service links.
    $page->fillField('localgov_homepage_ia_blocks[0][subform][localgov_ia_block_title][0][value]', 'Wordpress');
    $page->fillField('localgov_homepage_ia_blocks[0][subform][localgov_ia_block_link][0][uri]', 'https://wordpress.org/');
    $page->fillField('localgov_homepage_ia_blocks[0][subform][localgov_ia_block_links][0][title]', 'Plugins');
    $page->fillField('localgov_homepage_ia_blocks[0][subform][localgov_ia_block_links][0][uri]', 'https://wordpress.org/plugins/');

    // Newsroom teasers (without any Media image).
    $page->fillField('localgov_homepage_newsroom[0][subform][localgov_newsroom_teaser_title][0][value]', 'Drupal release news');
    $page->fillField('localgov_homepage_newsroom[0][subform][localgov_newsroom_teaser_link][0][uri]', 'https://www.drupal.org/project/drupal/releases/');
    $page->fillField('localgov_homepage_newsroom[0][subform][localgov_newsroom_teaser_summary][0][value]', 'A new Drupal version has been released.');

    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('css', 'div[aria-label="Error message"]');
  }

  /**
   * Theme to use during the functional tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

}
