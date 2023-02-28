<?php

namespace Drupal\Tests\feeds_tamper\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds_tamper\FeedTypeTamperMeta;
use Drupal\tamper\TamperInterface;
use Drupal\tamper\TamperManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds_tamper\FeedTypeTamperMeta
 * @group feeds_tamper
 */
class FeedTypeTamperMetaTest extends UnitTestCase {

  /**
   * The Tamper manager for a feed type.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMeta
   */
  protected $feedTypeTamperMeta;


  /**
   * The mock FeedType used to create the FeedTypeTamperMeta.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $mapping_sources = [
      'alpha' => [
        'label' => 'Alpha',
      ],
      'beta' => [
        'label' => 'Beta',
      ],
    ];

    $this->feedTypeTamperMeta = $this->createFeedTypeTamperMeta($mapping_sources);
  }

  /**
   * @covers ::getTamper
   */
  public function testGetTamper() {
    $tamper = $this->feedTypeTamperMeta->getTamper('uuid2');
    $this->assertInstanceOf(TamperInterface::class, $tamper);
  }

  /**
   * @covers ::getTampers
   */
  public function testGetTampers() {
    $tampers = iterator_to_array($this->feedTypeTamperMeta->getTampers());
    // Assert that two tampers exist in total.
    $this->assertCount(2, $tampers);
    // Assert that tampers with uuid 'uuid1' and 'uuid2' exist.
    $this->assertArrayHasKey('uuid1', $tampers);
    $this->assertArrayHasKey('uuid2', $tampers);
  }

  /**
   * @covers ::getTampersGroupedBySource
   */
  public function testGetTampersGroupedBySource() {
    $this->assertIsArray($this->feedTypeTamperMeta->getTampersGroupedBySource());
  }

  /**
   * @covers ::addTamper
   */
  public function testAddTamper() {
    $uuid = $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'convert_case',
      'operation' => 'ucfirst',
      'source' => 'gamma',
      'description' => 'Start text with uppercase character',
    ]);
    $this->assertEquals('uuid3', $uuid);

    $tamper = $this->feedTypeTamperMeta->getTamper($uuid);
    $this->assertInstanceOf(TamperInterface::class, $tamper);

    // Assert that three tampers exist in total.
    $this->assertCount(3, $this->feedTypeTamperMeta->getTampers());
  }

  /**
   * @covers ::setTamperConfig
   */
  public function testSetTamperConfig() {
    $separator = ':';
    $description = 'Explode with colon character (updated)';
    $this->assertEquals($this->feedTypeTamperMeta, $this->feedTypeTamperMeta->setTamperConfig('uuid1', [
      'separator' => $separator,
      'description' => $description,
    ]));
  }

  /**
   * @covers ::removeTamper
   */
  public function testRemoveTamper() {
    $this->feedTypeTamperMeta->removeTamper('uuid1');
    $tampers = iterator_to_array($this->feedTypeTamperMeta->getTampers());

    // Assert that uuid1 is removed, but uuid2 still exists.
    $this->assertArrayNotHasKey('uuid1', $tampers);
    $this->assertArrayHasKey('uuid2', $tampers);

    // Assert that one tamper exists in total.
    $this->assertCount(1, $tampers);
  }

  /**
   * @covers ::getSourceDefinition
   */
  public function testGetSourceDefinition() {
    // Test labels are in the source definition when we have them.
    $source = $this->feedTypeTamperMeta->getSourceDefinition();
    $source_list = $source->getList();
    $this->assertEquals($source_list['alpha'], 'Alpha');
    $this->assertEquals($source_list['beta'], 'Beta');
  }

  /**
   * @covers ::getSourceDefinition
   */
  public function testSourceDefinitionWithBlankLabels() {
    $mapping_sources = [
      'alpha' => [],
      'beta' => [],
    ];

    $feed_type_tamper_meta = $this->createFeedTypeTamperMeta($mapping_sources);

    // Assert that the source's keys are used as label in the source definition
    // when the sources do not provide labels.
    $source = $feed_type_tamper_meta->getSourceDefinition();
    $source_list = $source->getList();
    $this->assertEquals($source_list['alpha'], 'alpha');
    $this->assertEquals($source_list['beta'], 'beta');
  }

  /**
   * Creates a new FeedTypeTamperMeta.
   *
   * @param array $mapping_sources
   *   The array which will be returned by FeedType's mappingSources().
   *
   * @return Drupal\feeds_tamper\FeedTypeTamperMeta
   *   The FeedTypeTamperMeta object used for testing.
   */
  public function createFeedTypeTamperMeta(array $mapping_sources) {
    // Mock the UUID generator and let it always return 'uuid3'.
    $uuid_generator = $this->createMock(UuidInterface::class);
    $uuid_generator->expects($this->any())
      ->method('generate')
      ->will($this->returnValue('uuid3'));

    // Get the tamper manager.
    $tamper_manager = $this->createMock(TamperManagerInterface::class);
    $tamper_manager->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValue($this->createMock(TamperInterface::class)));

    // Mock the feed type and let it always return two tampers.
    $this->feed_type = $this->createMock(FeedTypeInterface::class);
    $this->feed_type->expects($this->any())
      ->method('getThirdPartySetting')
      ->with('feeds_tamper', 'tampers')
      ->will($this->returnValue([
        'uuid1' => [
          'uuid' => 'uuid1',
          'plugin' => 'explode',
          'separator' => '|',
          'source' => 'alpha',
          'description' => 'Explode with pipe character',
        ],
        'uuid2' => [
          'uuid' => 'uuid2',
          'plugin' => 'convert_case',
          'operation' => 'strtoupper',
          'source' => 'beta',
          'description' => 'Convert all characters to uppercase',
        ],
      ]));

    $this->feed_type->expects($this->any())
      ->method('getMappingSources')
      ->will($this->returnValue($mapping_sources));

    // Instantiate a feeds type tamper meta object.
    return new FeedTypeTamperMeta($uuid_generator, $tamper_manager, $this->feed_type);
  }

}
