<?php

namespace Drupal\Tests\no_autocomplete\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the module configuration form.
 *
 * @group no_autocomplete
 */
class NoAutoCompleteAdminSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['no_autocomplete'];

  /**
   * Tests the configuration form, the permission and the link.
   */
  public function testConfigurationForm() {
    // Going to the config page.
    $this->drupalGet('/admin/config/people/no_autocomplete');

    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

    // Creating a user with the module permission.
    $account = $this->drupalCreateUser(['administer no_autocomplete', 'access administration pages']);
    // Log in.
    $this->drupalLogin($account);

    // Checking the module link.
    $this->drupalGet('/admin/config/people');
    $this->assertSession()->linkByHrefExists('/admin/config/people/no_autocomplete');

    // Going to the config page.
    $this->drupalGet('/admin/config/people/no_autocomplete');
    // Checking that the request has succeeded.
    $this->assertSession()->statusCodeEquals(200);
    // Checking the page title.
    $this->assertSession()->elementTextContains('css', 'h1', 'No Autocomplete');
    // Check that the checkboxes are unchecked.
    $this->assertSession()->checkboxNotChecked('no_autocomplete_login_form');

    // Form values to send (checking checked checkboxes).
    $edit = [
      'no_autocomplete_login_form' => 1,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');
    // Verifiying the save message.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');

    // Getting variables.
    $no_autocomplete_login_form = $config_factory->get('no_autocomplete.settings')->get('no_autocomplete_login_form');

    // Verifiying that the config values are stored.
    $this->assertTrue($no_autocomplete_login_form, 'The configuration value for no_autocomplete_login_form should be TRUE.');

    // Form values to send (checking unchecked checkboxes).
    $edit = [
      'no_autocomplete_login_form' => 0,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');

    // Getting variables.
    $no_autocomplete_login_form = $config_factory->get('no_autocomplete.settings')->get('no_autocomplete_login_form');
    // Verifiying that the config values are stored.
    $this->assertFalse($no_autocomplete_login_form, 'The configuration value for no_autocomplete_login_form should be FALSE.');
  }

}
