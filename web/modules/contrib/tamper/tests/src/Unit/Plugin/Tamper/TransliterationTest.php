<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\Transliteration;
use Drupal\Component\Transliteration\PhpTransliteration;

/**
 * Tests the transliteration plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Transliteration
 * @group tamper
 */
class TransliterationTest extends TamperPluginTestBase {

  /**
   * A transliteration instance.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Transliteration([], 'transliteration', [], $this->getMockSourceDefinition(), $this->transliteration);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->transliteration = new PhpTransliteration();
    parent::setUp();
  }

  /**
   * Tests transliteration transformation of non-alphanumeric characters.
   */
  public function testTransliterationTransform() {
    $original = '90000012345678_Jäätelöä_Åbo_Spøgelsesjægerne_Günther_áé';
    $expected = '90000012345678_Jaateloa_Abo_Spogelsesjaegerne_Gunther_ae';

    $plugin = new Transliteration([], 'transliteration', [], $this->getMockSourceDefinition(), $this->transliteration);
    $this->assertEquals($expected, $plugin->tamper($original));
  }

}
