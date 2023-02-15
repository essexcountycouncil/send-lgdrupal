<?php

namespace Drupal\localgov_directories;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the directory facets entity type.
 */
class LocalgovDirectoriesFacetsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view directory facets');

      case 'update':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['edit directory facets', 'administer directory facets'],
          'OR'
        );

      case 'delete':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['delete directory facets', 'administer directory facets'],
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
      ['create directory facets', 'administer directory facets'],
      'OR'
    );
  }

}
