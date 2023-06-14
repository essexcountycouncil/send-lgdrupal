<?php

namespace Drupal\feeds\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;

/**
 * Checks if the current user has clear access to the items of the tempstore.
 */
class FeedClearMultipleAccessCheck extends FeedActionMultipleAccessCheck {

  /**
   * The action to check access for.
   */
  const ACTION = 'feeds_feed_multiple_clear_confirm';

  /**
   * {@inheritdoc}
   */
  protected function checkFeedAccess(AccountInterface $account, FeedInterface $feed) {
    return $feed->access('clear', $account);
  }

}
