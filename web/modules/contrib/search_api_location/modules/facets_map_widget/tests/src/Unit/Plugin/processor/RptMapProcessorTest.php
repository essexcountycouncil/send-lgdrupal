<?php

namespace Drupal\Tests\facets_map_widget\Unit\Plugin\processor;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\UrlProcessorHandler;
use Drupal\facets\Plugin\facets\url_processor\QueryString;
use Drupal\facets\Result\Result;
use Drupal\facets_map_widget\Plugin\facets\processor\RptMapProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group search_api_location
 * @coversDefaultClass \Drupal\facets_map_widget\Plugin\facets\processor\RptMapProcessor
 */
class RptMapProcessorTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * Contains RptMapProcessor object.
   *
   * @var \Drupal\facets_map_widget\Plugin\facets\processor\RptMapProcessor
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->processor = new RptMapProcessor([], 'rpt', []);

    $url_generator = $this->prophesize(UrlGeneratorInterface::class);
    $container = new ContainerBuilder();
    $container->set('url_generator', $url_generator->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuild() {
    // Create the Url processor.
    $queryString = $this->prophesize(QueryString::class);
    $queryString->getFilterKey()->willReturn('f');
    $queryString->getSeparator()->willReturn('::');
    $urlHandler = $this->prophesize(UrlProcessorHandler::class);
    $urlHandler->getProcessor()->willReturn($queryString->reveal());

    $facet = $this->prophesize(FacetInterface::class);
    $facet->getProcessors()->willReturn(['url_processor_handler' => $urlHandler->reveal()]);
    $facet->getUrlAlias()->willReturn('animals');

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = [
      new Result(
        $facet->reveal(),
        [
          "gridLevel",
          2,
          "columns",
          32,
          "rows",
          32,
          "minX",
          -180,
          "maxX",
          180,
          "minY",
          -90,
          "maxY",
          90,
          "counts_ints2D",
        ],
        'heatmap',
        1),
      new Result(
        $facet->reveal(),
        [
          "gridLevel",
          2,
          "columns",
          32,
          "rows",
          32,
          "minX",
          -180,
          "maxX",
          180,
          "minY",
          -90,
          "maxY",
          90,
          "counts_ints2D",
        ],
        'heatmap',
        1),
    ];
    $results[0]->setUrl(new Url('test'));
    $results[1]->setUrl(new Url('test'));

    $new_results = $this->processor->build($facet->reveal(), $results);

    $this->assertCount(2, $new_results);
    $params = UrlHelper::buildQuery(['f' => ['animals::(geom:__GEOM__)']]);
    $expected_route = 'route:test?' . $params;
    $this->assertEquals($expected_route, $new_results[0]->getUrl()->toUriString());
    $this->assertEquals($expected_route, $new_results[1]->getUrl()->toUriString());
  }

}
