<?php

namespace Drupal\Tests\no_autocomplete\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the module configurations.
 *
 * @group no_autocomplete
 */
class DefaultConfigurationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['no_autocomplete'];

  /**
   * Tests the default configuration values.
   */
  public function testDefaultConfigurationValues() {
    // Installing the configuration file.
    $this->installConfig(self::$modules);
    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');
    // Getting variable.
    $no_autocomplete_login_form = $config_factory->get('no_autocomplete.settings')->get('no_autocomplete_login_form');
    // Checking that the configuration variable is FALSE.
    $this->assertFalse($no_autocomplete_login_form, 'The default configuration value for no_autocomplete_login_form should be FALSE.');
  }

}
