<?php

namespace Drupal\feeds\Plugin\Action;

use Drupal\Core\Session\AccountInterface;

/**
 * Redirects to a feed import form.
 *
 * @Action(
 *   id = "feeds_feed_import_action",
 *   label = @Translation("Import selected feeds"),
 *   type = "feeds_feed",
 *   confirm_form_route_name = "feeds.multiple_import_confirm"
 * )
 */
class ImportFeedAction extends FeedActionBase {

  const ACTION = 'feeds_feed_multiple_import_confirm';

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('import', $account, $return_as_object);
  }

}
