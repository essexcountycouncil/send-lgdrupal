<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests cardinality settings for a Layout Paragraphs field widget.
 *
 * @group layout_paragraphs
 */
class CardinalityTest extends BuilderTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loginWithPermissions([
      'administer site configuration',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
    ]);
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_content/storage');
    $this->submitForm([
      'cardinality' => 'number',
      'cardinality_number' => 2,
    ], 'Save field settings');
  }

  /**
   * Tests cardinality settings for a Layout Paragraphs field widget.
   */
  public function testCardinality() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    // Add a three-column section.
    $this->addSectionComponent(2, '.lpb-btn--add');

    // Cardinality is set to 2. We should still have (+) buttons.
    $this->assertSession()->elementExists('css', '.layout__region--first .lpb-btn--add');
    $this->htmlOutput($this->getSession()->getPage()->getHtml());

    // Add a text component.
    $this->addTextComponent('Some arbitrary text', '.layout__region--first .lpb-btn--add');

    // Maximum number has been reached. There should be no more (+) buttons.
    $this->assertSession()->elementNotExists('css', '.layout__region--first .lpb-btn--add');
    $this->htmlOutput($this->getSession()->getPage()->getHtml());

    // Remove a component.
    $button = $page->find('css', '.layout__region--first a.lpb-delete');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $button = $page->find('css', 'button.lpb-btn--confirm-delete');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // We no longer have the maximum allowed items, and should have (+) buttons.
    $this->assertSession()->elementExists('css', '.layout__region--first .lpb-btn--add');
    $this->htmlOutput($this->getSession()->getPage()->getHtml());
  }

}
