<?php

namespace Drupal\Tests\localgov_services_navigation\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests localgov service landing pages unreferenced children list.
 *
 * @group localgov_services
 */
class LandingPageChildrenTest extends WebDriverTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user to edit landing pages.
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
    'localgov_services_navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'page']);
    $field_storage_config = FieldStorageConfig::loadByName('node', 'localgov_services_parent');
    $field_instance = FieldConfig::create([
      'field_storage' => $field_storage_config,
      'bundle' => 'page',
      'label' => $this->randomMachineName(),
    ]);
    $field_instance->save();
    $this->user = $this->drupalCreateUser([
      'access content',
      'create page content',
      'create localgov_services_landing content',
      'create localgov_services_sublanding content',
      'edit own localgov_services_landing content',
      'edit own localgov_services_sublanding content',
      'edit own page content',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test unreferenced children on landing page.
   */
  public function testServiceLandingPageLink() {
    $landing = $this->createNode([
      'title' => 'Landing Page 1',
      'type' => 'localgov_services_landing',
      'body' => [
        'summary' => $this->randomString(100),
        'value' => $this->randomString(100),
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $landing->save();

    $child[1] = $this->createNode([
      'title' => 'child "> &one\' <"',
      'type' => 'page',
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $child[1]->save();

    $child[2] = $this->createNode([
      'title' => '\'; #child_2\n',
      'type' => 'page',
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'path' => ['alias' => '/foo'],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $child[2]->save();

    $child[3] = $this->createNode([
      'title' => '( bob )";',
      'type' => 'page',
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $child[3]->save();

    $this->drupalGet($landing->toUrl('edit-form')->toString());
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->elementExists('css', '#localgov-child-drag-' . $child[1]->id());
    // Check attributes. Yes they're returned decoded.
    $assert_session->elementAttributeContains('css', '#localgov-child-drag-' . $child[1]->id(), 'data-localgov-url', $child[1]->toUrl()->toString());
    $assert_session->elementAttributeContains('css', '#localgov-child-drag-' . $child[1]->id(), 'data-localgov-title', 'child "> &one\' <"');
    $assert_session->elementAttributeContains('css', '#localgov-child-drag-' . $child[1]->id(), 'data-localgov-reference', 'child "> &one\' <" (' . $child[1]->id() . ')');
    // So also check encoding.
    $element = $page->find('css', '#localgov-child-drag-' . $child[1]->id());
    $this->assertStringContainsString('child &quot;> &amp;one\' <&quot;', $element->getOuterHtml());
    $element = $page->find('css', '#localgov-child-drag-' . $child[1]->id() . ' .localgov-child-title');
    $this->assertStringContainsString('child "&gt; &amp;one\' &lt;"', $element->getHtml());

    // Drag the child to a Tasks Link field.
    $this->clickLink('Common tasks');
    $drag = $page->find('css', '#localgov-child-drag-' . $child[1]->id());
    $target = $page->find('css', '#edit-localgov-common-tasks-0-uri');
    $drag->dragTo($target);
    // Check it got populated.
    $assert_session->fieldValueEquals('edit-localgov-common-tasks-0-uri', $child[1]->toUrl()->toString());
    $assert_session->fieldValueEquals('edit-localgov-common-tasks-0-title', 'child "> &one\' <"');

    // Drag the child to a Tasks Link field.
    $drag = $page->find('css', '#localgov-child-drag-' . $child[2]->id());
    $target = $page->find('css', '#edit-localgov-common-tasks-1-uri');
    $drag->dragTo($target);
    // Check it got populated.
    $assert_session->fieldValueEquals('edit-localgov-common-tasks-1-uri', '/foo');
    $assert_session->fieldValueEquals('edit-localgov-common-tasks-1-title', '\'; #child_2\n');

    // Drag the child to a populated Tasks Link field.
    $drag = $page->find('css', '#localgov-child-drag-' . $child[3]->id());
    $target = $page->find('css', '#edit-localgov-common-tasks-1-uri');
    $drag->dragTo($target);
    // Shouldn't overwrite.
    $assert_session->fieldValueEquals('edit-localgov-common-tasks-1-uri', '/foo');
    $assert_session->fieldValueEquals('edit-localgov-common-tasks-1-title', '\'; #child_2\n');

  }

  /**
   * Test unreferenced children on landing page.
   */
  public function testServiceLandingPageReference() {
    $landing = $this->createNode([
      'title' => 'Landing Page 1',
      'type' => 'localgov_services_landing',
      'body' => [
        'summary' => $this->randomString(100),
        'value' => $this->randomString(100),
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $landing->save();

    $this->createContentType(['type' => 'localgov_services_status']);
    $field_storage_config = FieldStorageConfig::loadByName('node', 'localgov_services_parent');
    $field_instance = FieldConfig::create([
      'field_storage' => $field_storage_config,
      'bundle' => 'localgov_services_status',
      'label' => $this->randomMachineName(),
    ]);
    $field_instance->save();
    $status = $this->createNode([
      'title' => 'status update listed on page elsewhere automatically',
      'type' => 'localgov_services_status',
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $status->save();

    $child[3] = $this->createNode([
      'title' => '( bob )";',
      'type' => 'page',
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $child[3]->save();
    $child[4] = $this->createNode([
      'title' => 'and last one left',
      'type' => 'page',
      'localgov_services_parent' => ['target_id' => $landing->id()],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $child[4]->save();

    $this->drupalGet($landing->toUrl('edit-form')->toString());
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Check status page in the list of unreferenced children.
    $assert_session->pageTextNotContains('status update listed on page elsewhere automatically');

    // Drag the child to a Tasks Link field.
    $this->clickLink('Child pages');
    $drag = $page->find('css', '#localgov-child-drag-' . $child[3]->id());
    $target = $page->find('css', '#edit-localgov-destinations-0-target-id');
    $drag->dragTo($target);
    // Check it got populated.
    $assert_session->fieldValueEquals('edit-localgov-destinations-0-target-id', '( bob )"; (' . $child[3]->id() . ')');
  }

}
