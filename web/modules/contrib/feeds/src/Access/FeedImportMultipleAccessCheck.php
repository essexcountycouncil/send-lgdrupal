<?php

namespace Drupal\feeds\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;

/**
 * Checks if the current user has import access to the feeds of the tempstore.
 */
class FeedImportMultipleAccessCheck extends FeedActionMultipleAccessCheck {

  /**
   * The action to check access for.
   */
  const ACTION = 'feeds_feed_multiple_import_confirm';

  /**
   * {@inheritdoc}
   */
  protected function checkFeedAccess(AccountInterface $account, FeedInterface $feed) {
    return $feed->access('import', $account);
  }

}
