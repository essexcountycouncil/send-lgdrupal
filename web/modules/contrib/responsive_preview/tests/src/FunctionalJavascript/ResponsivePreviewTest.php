<?php

namespace Drupal\Tests\responsive_preview\FunctionalJavascript;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the toolbar integration.
 *
 * @group responsive_preview
 */
class ResponsivePreviewTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'node',
    'responsive_preview',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $previewUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    $this->previewUser = $this->drupalCreateUser([
      'access responsive preview',
      'access toolbar',
      'view test entity',
      'administer entity_test content',
      'create article content',
      'edit own article content',
    ]);
  }

  /**
   * Tests that the toolbar integration works properly.
   */
  public function testToolbarIntegration() {
    $entity = EntityTest::create();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $this->drupalLogin($this->previewUser);

    $this->drupalGet($entity->toUrl());
    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $this->assertSession()->elementNotExists('xpath', '//*[@id="responsive-preview-orientation" and contains(@class, "rotated")]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertTrue($this->getSession()->evaluateScript("jQuery('#responsive-preview-frame')[0].contentWindow.location.href.endsWith('/entity_test/1')"));
  }

  /**
   * Tests that preview works on node edit.
   */
  public function testContentEdit() {
    $this->drupalLogin($this->previewUser);

    $node = Node::create([
      'type' => 'article',
      'uid' => $this->previewUser->id(),
      'title' => $this->randomString(),
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');

    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $this->assertSession()->elementNotExists('xpath', '//*[@id="responsive-preview-orientation" and contains(@class, "rotated")]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertTrue($this->getSession()->evaluateScript(
      "jQuery('#responsive-preview-frame')[0].contentWindow.location.href.endsWith('/node/preview/" . $node->uuid() . "/full')"
    ));
  }

  /**
   * Select device for device preview.
   *
   * NOTE: Index starts from 1.
   *
   * @param int $xpath_device_button
   *   The index number of device in drop-down list.
   */
  protected function selectDevice($xpath_device_button) {
    $page = $this->getSession()->getPage();

    $page->find('xpath', '//*[@id="responsive-preview-toolbar-tab"]/button')
      ->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->find('xpath', $xpath_device_button)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
