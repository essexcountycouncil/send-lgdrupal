<?php

namespace Drupal\Tests\facets_map_widget\Unit\Plugin\query_type;

use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets_map_widget\Plugin\facets\query_type\SearchApiRpt;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for query type.
 *
 * @group search_api_location
 */
class SearchApiRptTest extends UnitTestCase {

  /**
   * Tests rpt query type without executing the query with an "AND" operator.
   */
  public function testQueryTypeAnd() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'and'],
      'facets_facet'
    );

    $original_results = [
      ['count' => 3, 'filter' => 'heatmap'],
    ];

    $query_type = new SearchApiRpt(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_rpt',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertTrue(is_array($results));

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf(ResultInterface::class, $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests rpt query type without executing the query with an "OR" operator.
   */
  public function testQueryTypeOr() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'or'],
      'facets_facet'
    );
    $facet->setFieldIdentifier('field_animal');

    $original_results = [
      ['count' => 8, 'filter' => 'heatmap'],
    ];

    $query_type = new SearchApiRpt(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_rpt',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertTrue(is_array($results));

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf(ResultInterface::class, $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests rpt query type without results.
   */
  public function testEmptyResults() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $query_type = new SearchApiRpt(
      [
        'facet' => $facet,
        'query' => $query,
      ],
      'search_api_rpt',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertTrue(is_array($results));
    $this->assertEmpty($results);
  }

  /**
   * Tests rpt query type without results.
   */
  public function testConfiguration() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $default_config = ['facet' => $facet, 'query' => $query];
    $query_type = new SearchApiRpt($default_config, 'search_api_rpt', []);

    $this->assertEquals([], $query_type->defaultConfiguration());
    $this->assertEquals($default_config, $query_type->getConfiguration());

    $query_type->setConfiguration(['owl' => 'Long-eared owl']);
    $this->assertEquals(['owl' => 'Long-eared owl'], $query_type->getConfiguration());
  }

}
