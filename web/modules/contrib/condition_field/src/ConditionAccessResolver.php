<?php

namespace Drupal\condition_field;

use Drupal\Core\Condition\ConditionAccessResolverTrait;

/**
 * Defines a generic condition access resolver.
 */
class ConditionAccessResolver {

  use ConditionAccessResolverTrait;

  // TODO: do more conditions related work.

  /**
   * Resolves the given conditions based on the condition logic ('and'/'or').
   *
   * @see ConditionAccessResolverTrait::resolveConditions()
   */
  public static function checkAccess($conditions, $condition_logic) {
    return (new self)->resolveConditions($conditions, $condition_logic);
  }

}
