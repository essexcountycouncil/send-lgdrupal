<?php

namespace Drupal\feeds\Plugin\Action;

use Drupal\Core\Session\AccountInterface;

/**
 * Redirects to a feed clear form.
 *
 * @Action(
 *   id = "feeds_feed_clear_action",
 *   label = @Translation("Delete imported items of selected feeds"),
 *   type = "feeds_feed",
 *   confirm_form_route_name = "feeds.multiple_clear_confirm"
 * )
 */
class ClearFeedAction extends FeedActionBase {

  const ACTION = 'feeds_feed_multiple_clear_confirm';

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('clear', $account, $return_as_object);
  }

}
