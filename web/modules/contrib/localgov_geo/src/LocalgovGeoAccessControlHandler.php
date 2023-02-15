<?php

namespace Drupal\localgov_geo;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the geo entity type.
 */
class LocalgovGeoAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission(
          $account,
          'view geo'
        );

      case 'update':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['edit geo', 'administer geo'],
          'OR'
        );

      case 'delete':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['delete geo', 'administer geo'],
          'OR'
        );

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions(
      $account,
      ['create geo', 'administer geo'],
      'OR'
    );
  }

}
