<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the html entity decode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\HtmlEntityDecode
 * @group tamper
 */
class HtmlEntityDecodeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'html_entity_decode';

}
