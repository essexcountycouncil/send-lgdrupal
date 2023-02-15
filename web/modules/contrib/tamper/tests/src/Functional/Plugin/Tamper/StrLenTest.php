<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the strlen plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrLen
 * @group tamper
 */
class StrLenTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'str_len';

}
