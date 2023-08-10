<?php

namespace Drupal\Tests\localgov_step_by_step\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Tests localgov step by step pages working together.
 *
 * @group localgov_step_by_step
 */
class StepByStepPagesTest extends BrowserTestBase {

  use NodeCreationTrait;
  use AssertBreadcrumbTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_core',
    'localgov_step_by_step',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'administer node fields',
    ]);
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
  }

  /**
   * Verifies basic functionality with all modules.
   */
  public function testConfigForm() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/types/manage/localgov_step_by_step_overview/fields');
    $this->assertSession()->pageTextContains('localgov_step_description');
    $this->assertSession()->pageTextContains('localgov_step_by_step_pages');

    $this->drupalGet('/admin/structure/types/manage/localgov_step_by_step_page/fields');
    $this->assertSession()->pageTextContains('localgov_step_parent');
    $this->assertSession()->pageTextContains('localgov_step_section_title');
    $this->assertSession()->pageTextContains('localgov_step_summary');
  }

}
