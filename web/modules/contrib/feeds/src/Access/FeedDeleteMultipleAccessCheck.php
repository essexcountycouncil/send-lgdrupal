<?php

namespace Drupal\feeds\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;

/**
 * Checks if the current user has delete access to the items of the tempstore.
 */
class FeedDeleteMultipleAccessCheck extends FeedActionMultipleAccessCheck {

  /**
   * The action to check access for.
   */
  const ACTION = 'feeds_feed_multiple_delete_confirm';

  /**
   * {@inheritdoc}
   */
  protected function checkFeedAccess(AccountInterface $account, FeedInterface $feed) {
    return $feed->access('delete', $account);
  }

}
