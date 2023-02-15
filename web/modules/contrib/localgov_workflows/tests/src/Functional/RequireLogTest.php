<?php

namespace Drupal\Tests\localgov_workflows\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests requiring log revisions message on node bundles.
 *
 * @group localgov_workflows
 */
class RequireLogTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'localgov_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'require_log_test',
        'name' => 'Require log test',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'create require_log_test content',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Create a new content type change log required status.
   */
  public function testRequireLogContentType() {
    // Default is for content type to have revisions.
    // Require log message too.
    $this->drupalGet('/admin/structure/types/manage/require_log_test');
    $this->assertSession()->responseContains('Require revision log message');
    // Enabling field_ui will change that button.
    $this->submitForm([
      'options[revision_required]' => TRUE,
    ], 'Save content type');

    $this->drupalGet('/node/add/require_log_test');
    $log_field = $this->assertSession()->fieldExists('revision_log[0][value]');
    $this->assertEquals('required', $log_field->getAttribute('required'));

    // Check box is checked, and disable it.
    $this->drupalGet('/admin/structure/types/manage/require_log_test');
    $checkbox = $this->assertSession()->fieldExists('options[revision_required]');
    $this->assertTrue($checkbox->isChecked());
    $this->submitForm([
      'options[revision_required]' => FALSE,
    ], 'Save content type');

    $this->drupalGet('/node/add/require_log_test');
    $log_field = $this->assertSession()->fieldExists('revision_log[0][value]');
    $this->assertEmpty($log_field->getAttribute('required'));

    // Do that again and check box isn't checked.
    $this->drupalGet('/admin/structure/types/manage/require_log_test');
    $checkbox = $this->assertSession()->fieldExists('options[revision_required]');
    $this->assertFalse($checkbox->isChecked());
    $this->submitForm([
      'options[revision_required]' => TRUE,
    ], 'Save content type');

    $this->drupalGet('/node/add/require_log_test');
    $log_field = $this->assertSession()->fieldExists('revision_log[0][value]');
    $this->assertEquals('required', $log_field->getAttribute('required'));

    // But then disable revisions, should unset the required field.
    $this->drupalGet('/admin/structure/types/manage/require_log_test');
    $checkbox = $this->assertSession()->fieldExists('options[revision_required]');
    $this->assertTrue($checkbox->isChecked());
    $this->submitForm([
      'options[revision]' => FALSE,
    ], 'Save content type');
    $this->drupalGet('/node/add/require_log_test');
    $log_field = $this->assertSession()->fieldNotExists('revision_log[0][value]');
    $this->drupalGet('/admin/structure/types/manage/require_log_test');
    $checkbox = $this->assertSession()->fieldExists('options[revision_required]');
    $this->assertFalse($checkbox->isChecked());
  }

}
