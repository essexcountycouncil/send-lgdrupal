<?php

namespace Drupal\Tests\localgov_review_date\Functional;

use Drupal\localgov_roles\RolesHelper;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the permissions for review statuses.
 */
class RoleAccessTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_services_page',
    'localgov_review_date',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * Example page to test access to.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $page;

  /**
   * Test the alert banner user access permissions.
   */
  public function testUserAccess() {

    // Check editor can access and save nodes with review status field.
    $this->reviewPage(RolesHelper::EDITOR_ROLE, date('Y-m-d', strtotime('+3 months')));
    $this->reviewPage(RolesHelper::AUTHOR_ROLE, date('Y-m-d', strtotime('+6 months')));
    $this->reviewPage(RolesHelper::CONTRIBUTOR_ROLE, date('Y-m-d', strtotime('+9 months')));
  }

  /**
   * Check user can access and save node with review status.
   *
   * @param string $role
   *   User role to test editing with.
   * @param string $date
   *   Date to set as next review in 'Y-m-d' format.
   */
  protected function reviewPage($role, $date) {
    $assert_session = $this->assertSession();

    // Create user with role.
    $user = $this->createUser();
    $user->addRole($role);
    $user->save();
    $this->drupalLogin($user);

    // Check page creation.
    $title = $this->randomString(12);
    $this->drupalGet('node/add/localgov_services_page');
    $assert_session->statusCodeEquals(Response::HTTP_OK);
    $assert_session->elementExists('css', '.review-date-form');
    $edit = [
      'title[0][value]' => $title,
      'body[0][summary]' => $this->randomString(12),
      'body[0][value]' => $this->randomString(12),
      'localgov_review_date[0][reviewed]' => TRUE,
      'localgov_review_date[0][review][review_date]' => $date,
    ];
    $this->submitForm($edit, 'Save');
    $page = $this->drupalGetNodeByTitle($title);
    $this->drupalGet('node/' . $page->id() . '/edit');
    $assert_session->elementExists('css', '.review-date-form');
    $assert_session->hiddenFieldValueEquals('localgov_review_date[0][last_review]', date('Y-m-d'));
    $assert_session->hiddenFieldValueEquals('localgov_review_date[0][next_review]', $date);

    $this->drupalLogout();
  }

}
