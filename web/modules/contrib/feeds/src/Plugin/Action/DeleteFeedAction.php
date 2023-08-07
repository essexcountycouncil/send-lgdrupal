<?php

namespace Drupal\feeds\Plugin\Action;

use Drupal\Core\Session\AccountInterface;

/**
 * Redirects to a feed deletion form.
 *
 * @Action(
 *   id = "feeds_feed_delete_action",
 *   label = @Translation("Delete selected feeds"),
 *   type = "feeds_feed",
 *   confirm_form_route_name = "feeds.multiple_delete_confirm"
 * )
 */
class DeleteFeedAction extends FeedActionBase {

  const ACTION = 'feeds_feed_multiple_delete_confirm';

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('delete', $account, $return_as_object);
  }

}
