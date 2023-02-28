<?php

namespace Drupal\Tests\no_autocomplete\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the form elements.
 *
 * @group no_autocomplete
 */
class FormTest extends BrowserTestBase {

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
   * Tests the log in form.
   */
  public function testUserLoginForm() {
    // Going to the log in.
    $this->drupalGet('/user/login');

    // Checking that the autocomplete is not present.
    $this->assertSession()->elementNotExists('css', '#edit-pass[autocomplete]');

    // Creating a user with the module permission.
    $account = $this->drupalCreateUser(['administer no_autocomplete']);
    // Log in.
    $this->drupalLogin($account);

    // Going to the config page.
    $this->drupalGet('/admin/config/people/no_autocomplete');

    // Form values to send (checking checked checkboxes).
    $edit = [
      'no_autocomplete_login_form' => 1,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');

    // Log out.
    $this->drupalLogout();

    // Going to the log in.
    $this->drupalGet('/user/login');

    // Checking that the autocomplete is set off.
    $this->assertSession()->elementAttributeContains('css', '#edit-pass', 'autocomplete', 'off');
  }

}
