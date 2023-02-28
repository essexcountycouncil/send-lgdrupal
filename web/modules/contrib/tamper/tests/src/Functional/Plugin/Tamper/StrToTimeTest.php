<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the strtotime plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrToTime
 * @group tamper
 */
class StrToTimeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'strtotime';

}
