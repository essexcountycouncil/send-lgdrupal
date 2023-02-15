<?php

namespace Drupal\Tests\localgov_services_navigation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the output of entity reference autocomplete widgets.
 *
 * @group entity_reference
 */
class EntityReferenceServicesAutocompleteTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;

  /**
   * A user with mininum permissions for test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_core',
    'localgov_services',
    'localgov_services_landing',
    'localgov_services_sublanding',
    'localgov_services_page',
    'localgov_services_navigation',
    'field_ui',
  ];


  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode([
      'title' => 'Landing Page 1',
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->createNode([
      'title' => 'Page Sub 1',
      'type' => 'localgov_services_sublanding',
      'localgov_services_parent' => ['target_id' => 1],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->user = $this->drupalCreateUser([
      'access content',
      'create page content',
      'create localgov_services_landing content',
      'create localgov_services_sublanding content',
      'create localgov_services_page content',
      'edit own localgov_services_page content',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests services content types default fields return the correct results.
   */
  public function testServicesEntityReferenceAutocompleteWidget() {
    $field_name = 'localgov_services_parent';

    $this->drupalGet('node/add/localgov_services_sublanding');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="' . $field_name . '[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Page');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Return the landing page, not another sublanding page.
    $assert_session->pageTextContains('Landing Page 1');

    $this->drupalGet('node/add/localgov_services_page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="' . $field_name . '[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Page');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(2, $results);
    // Return the landing page, not another sublanding page.
    $assert_session->pageTextContains('Landing Page 1');
    $assert_session->pageTextContains('Landing Page 1 » Page Sub 1');

  }

  /**
   * Tests the services autocomplete handler attached to a new content type.
   */
  public function testEntityReferenceAutocompleteWidget() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Create an entity reference field and use the default 'CONTAINS' match
    // operator.
    $field_name = 'field_test';
    $this->createEntityReferenceField(
      'node',
      'page',
      $field_name,
      $field_name,
      'node',
      'localgov_services',
      [
        'target_bundles' => [
          'localgov_services_landing',
          'localgov_services_sublanding',
        ],
      ]
    );
    $form_display = $display_repository->getFormDisplay('node', 'page');
    $form_display->setComponent($field_name, [
      'type' => 'entity_reference_autocomplete',
      'settings' => [
        'match_operator' => 'CONTAINS',
      ],
    ]);
    // To satisfy config schema, the size setting must be an integer, not just
    // a numeric value. See https://www.drupal.org/node/2885441.
    $this->assertIsInt($form_display->getComponent($field_name)['settings']['size']);
    $form_display->save();
    $this->assertIsInt($form_display->getComponent($field_name)['settings']['size']);

    // Visit the node add page.
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="' . $field_name . '[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Page');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();
    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(2, $results);
    $assert_session->pageTextContains('Landing Page 1');
    $assert_session->pageTextContains('Landing Page 1 » Page Sub 1');

    $autocomplete_field->setValue('');
    $assert_session->waitForElementRemoved('css', '.ui-autocomplete li');

    $autocomplete_field->setValue('Sub');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();
    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(1, $results);
    $assert_session->pageTextContainsOnce('Landing Page 1');
    $assert_session->pageTextContains('Landing Page 1 » Page Sub 1');

    // Now switch the autocomplete widget to the 'STARTS_WITH' match operator.
    $display_repository->getFormDisplay('node', 'page')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'STARTS_WITH',
        ],
      ])
      ->save();

    $this->drupalGet('node/add/page');

    $autocomplete_field = $this->getSession()->getPage()->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue('Page');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(1, $results);
    $assert_session->pageTextContainsOnce('Landing Page 1');
    $assert_session->pageTextContains('Landing Page 1 » Page Sub 1');
  }

  /**
   * Test the label display of the selected saved sub landing page.
   */
  public function testEntityReferenceSublandingLabelWidget() {
    $node = $this->createNode([
      'title' => 'Page in a Service',
      'type' => 'localgov_services_page',
      'localgov_services_parent' => ['target_id' => 2],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('edit-localgov-services-parent-0-target-id', 'Landing Page 1 » Page Sub 1 (2)');
  }

}
