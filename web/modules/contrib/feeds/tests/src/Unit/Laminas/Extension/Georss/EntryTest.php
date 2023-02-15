<?php

namespace Drupal\Tests\feeds\Unit\Laminas\Extension\Georss;

use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Laminas\Extension\Georss\Entry;

/**
 * @coversDefaultClass \Drupal\feeds\Laminas\Extension\Georss\Entry
 * @group feeds
 */
class EntryTest extends FeedsUnitTestCase {

  /**
   * @covers ::setXpath
   * @covers ::setEntryElement
   * @covers ::getGeoPoint
   */
  public function test() {
    $text = '<feed xmlns:georss="http://www.georss.org/georss">';
    $text .= '<entry><georss:point>45.256 -71.92</georss:point></entry>';
    $text .= '</feed>';

    $doc = new \DOMDocument();
    $doc->loadXML($text);

    $entry = new Entry();
    $entry->setXpath(new \DOMXPath($doc));

    $entry->setEntryElement($doc->getElementsByTagName('entry')->item(0));

    $point = $entry->getGeoPoint();
    $this->assertSame(45.256, $point['lat']);
    $this->assertSame(-71.92, $point['lon']);
  }

}
