<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the html entity encode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\HtmlEntityEncode
 * @group tamper
 */
class HtmlEntityEncodeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'html_entity_encode';

}
