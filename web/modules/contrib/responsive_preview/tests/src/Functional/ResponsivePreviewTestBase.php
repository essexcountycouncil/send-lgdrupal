<?php

namespace Drupal\Tests\responsive_preview\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Responsive preview base test class.
 */
abstract class ResponsivePreviewTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['responsive_preview'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Return the default devices.
   *
   * @param bool $enabled_only
   *   Whether return only devices enabled by default.
   *
   * @return array
   *   An array of the default devices.
   */
  protected function getDefaultDevices($enabled_only = FALSE) {
    $devices = [
      'galaxy_s9' => 'Galaxy S9',
      'galaxy_tab_s4' => 'Galaxy Tab S4',
      'ipad_pro' => 'iPad Pro',
      'iphone_xs' => 'iPhone XS',
      'iphone_xs_max' => 'iPhone XS Max',
    ];

    if ($enabled_only) {
      return $devices;
    }

    $devices += [
      'large' => 'Typical desktop',
      'medium' => 'Tablet',
      'small' => 'Smart phone',
    ];

    return $devices;
  }

  /**
   * Tests exposed devices in the responsive preview list.
   *
   * @param array $devices
   *   An array of devices to check.
   */
  protected function assertDeviceListEquals(array $devices) {
    $device_buttons = $this->xpath('//button[@data-responsive-preview-name]');
    $this->assertTrue(count($devices) === count($device_buttons));

    foreach ($device_buttons as $button) {
      $name = $button->getAttribute('data-responsive-preview-name');
      $this->assertTrue(!empty($name) && in_array($name, $devices), new FormattableMarkup('%name device shown', ['%name' => $name]));
    }
  }

  /**
   * Asserts whether responsive preview cache metadata is present.
   */
  protected function assertResponsivePreviewCachesTagAndContexts() {
    $this->assertSession()
      ->responseHeaderContains('X-Drupal-Cache-Tags', 'config:responsive_preview_device_list');
    /*
     * @todo Bring back when
     * https://www.drupal.org/project/drupal/issues/2962320 is fixed.
     * $this->assertSession()
     * ->responseHeaderContains('X-Drupal-Cache-Contexts', 'route.is_admin');
     */
  }

  /**
   * Asserts whether responsive preview cache metadata is not present.
   */
  protected function assertNoResponsivePreviewCachesTagAndContexts() {
    $this->assertSession()
      ->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:responsive_preview_device_list');
    /*
     * @todo Bring back when
     * https://www.drupal.org/project/drupal/issues/2962320 is fixed.
     * $this->assertSession()
     * ->responseHeaderNotContains('X-Drupal-Cache-Contexts', 'route.is_admin');
     */
  }

  /**
   * Asserts whether responsive preview library is included.
   */
  protected function assertResponsivePreviewLibrary() {
    $this->assertSession()
      ->responseContains('responsive_preview/js/responsive-preview.js');
    $this->assertSession()
      ->responseContains('responsive_preview/css/responsive-preview.icons.css');
    $this->assertSession()
      ->responseContains('responsive_preview/css/responsive-preview.module.css');
    $this->assertSession()
      ->responseContains('responsive_preview/css/responsive-preview.theme.css');
  }

  /**
   * Asserts whether responsive preview library is not included.
   */
  protected function assertNoResponsivePreviewLibrary() {
    $this->assertSession()
      ->responseNotContains('responsive_preview/js/responsive-preview.js');
    $this->assertSession()
      ->responseNotContains('responsive_preview/css/responsive-preview.icons.css');
    $this->assertSession()
      ->responseNotContains('responsive_preview/css/responsive-preview.module.css');
    $this->assertSession()
      ->responseNotContains('responsive_preview/css/responsive-preview.theme.css');
  }

}
