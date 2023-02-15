<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\StateToAbbrev;

/**
 * Tests the state to abbreviation plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StateToAbbrev
 * @group tamper
 */
class StateToAbbrevTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StateToAbbrev([], 'state_to_abbrev', [], $this->getMockSourceDefinition());
  }

  /**
   * Test lower case California.
   */
  public function testLowerCaseCalifornia() {
    $this->assertEquals('CA', $this->plugin->tamper('california'));
  }

  /**
   * Test existing state code.
   */
  public function testExistingStateCode() {
    $this->assertEquals('MA', $this->plugin->tamper('massachusetts'));
  }

  /**
   * Test upper case Hawaii.
   */
  public function testUpperCaseHawaii() {
    $this->assertEquals('HI', $this->plugin->tamper('HAWAII'));
  }

  /**
   * Test mixed case Massachusetts.
   */
  public function testMixedCaseMassachusetts() {
    $this->assertEquals('MA', $this->plugin->tamper('MaSsAcHuSeTtS'));
  }

  /**
   * Test Northern Mariana Islands.
   */
  public function testNorthernMarianaIslands() {
    $this->assertEquals('MP', $this->plugin->tamper('Northern Mariana Islands'));
  }

  /**
   * Test State Not Found.
   */
  public function testStateNotFound() {
    $this->assertEquals('', $this->plugin->tamper('xyz'));
  }

}
