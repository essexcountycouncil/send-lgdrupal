<?php

namespace Drupal\Tests\responsive_preview\Functional;

/**
 * Tests the responsive preview block.
 *
 * @group responsive_preview
 */
class ResponsivePreviewBlockTest extends ResponsivePreviewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * Tests responsive preview block.
   */
  public function testBlock() {
    $devices = array_keys($this->getDefaultDevices(TRUE));
    $this->placeBlock('responsive_preview_block');

    // Anonymous user by default cannot use the preview so the module library
    // and the cache tags and contexts should not be present.
    $this->drupalGet('');
    $this->assertSession()->elementNotExists('css', '.block-responsive-preview');
    $this->assertNoResponsivePreviewLibrary();
    $this->assertNoResponsivePreviewCachesTagAndContexts();

    // Users with 'access responsive preview' permission can use the preview so
    // the module library and the cache tags and contexts should be included.
    $preview_user = $this->drupalCreateUser(['access responsive preview']);
    $this->drupalLogin($preview_user);
    $this->assertSession()->elementExists('css', '.block-responsive-preview');
    $this->assertResponsivePreviewLibrary();
    $this->assertResponsivePreviewCachesTagAndContexts();
    $this->assertDeviceListEquals($devices);
  }

}
