<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_hierarchy_microsite\Traits;

/**
 * Defines a trait for rebuilding menu if needed.
 */
trait MenuRebuildTrait {

  /**
   * Triggers menu rebuilding if it's needed.
   */
  protected function triggerMenuRebuild(): void {
    \Drupal::service('entity_hierarchy_microsite.menu_rebuild_processor')->destruct();
  }

}
