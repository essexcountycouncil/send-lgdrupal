<?php

namespace Drupal\facets_map_widget\Plugin\facets\widget;

use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * A widget class that provides a map interface to narrow down facet results.
 *
 * @FacetsWidget(
 *   id = "rpt",
 *   label = @Translation("Interactive map showing the clustered heatmap"),
 *   description = @Translation("A configurable widget that builds an location array with results."),
 * )
 */
class RptMapWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    /** @var \Drupal\facets\Result\Result[] $results */
    $results = $facet->getResults();
    $build['map'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['facets-map'],
        'id' => $facet->id(),
      ],
    ];
    $build['#attached']['library'][] = 'facets_map_widget/facets_map';
    $build['#attached']['drupalSettings']['facets']['map'] = [
      'id' => $facet->id(),
      'url' => $results[0]->getUrl()->toString(),
      'results' => json_encode($results[0]->getRawValue()),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function isPropertyRequired($name, $type) {
    if ($name === 'rpt' && $type === 'processors') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'search_api_rpt';
  }

}
