<?php

namespace Drupal\localgov_services_status\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Views style plugin for displaying Service status.
 *
 * The Service status should appear as an accordion.  But in wider screens, it
 * should appear as a vertical tab.
 *
 * We look at each row of the View's result, extract the base entity from that
 * row, and use the entity label as the header of the Tab or Accordion.
 *
 * The rendered row is then used as the **content** of the Tab or Accordion.
 *
 * @see https://codepen.io/axelaredz/pen/OEXdPv
 * @see views-view-localgov-services-status.html.twig
 *
 * @ViewsStyle(
 *   id = "ServiceStatus",
 *   title = "Service status",
 *   help = @Translation("Render Service status as both accordion and tabbed content for different display widths."),
 *   theme = "views_view_localgov_services_status",
 *   display_types = {"normal"}
 * )
 */
class ServiceStatus extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support adding fields to its output?
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Does the style plugin support grouping of rows?
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Does the style plugin support custom css class for the rows?
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

}
