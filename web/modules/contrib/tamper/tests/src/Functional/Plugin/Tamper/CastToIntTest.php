<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the cast to int plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\CastToInt
 * @group tamper
 */
class CastToIntTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'cast_to_int';

}
