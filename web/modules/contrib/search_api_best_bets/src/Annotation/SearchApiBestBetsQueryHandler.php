<?php

namespace Drupal\search_api_best_bets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object.
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class SearchApiBestBetsQueryHandler extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The plugins label.
   *
   * @var string
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var string
   */
  public $description;

  /**
   * Supported search backends.
   *
   * @var array
   */
  public $backends;

}
