<?php

namespace Drupal\feeds\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\feeds\FeedInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks if the current user has access to the items of the tempstore.
 */
abstract class FeedActionMultipleAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new FeedActionMultipleAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStore = $temp_store_factory->get(static::ACTION);
    $this->requestStack = $request_stack;
  }

  /**
   * Checks if the user has access for at least one item of the store.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed or forbidden, neutral if tempstore is empty.
   */
  public function access(AccountInterface $account) {
    if (!$this->requestStack->getCurrentRequest()->hasSession()) {
      return AccessResult::neutral();
    }
    $selection = $this->tempStore->get($account->id() . ':feeds_feed');
    if (empty($selection) || !is_array($selection)) {
      return AccessResult::neutral();
    }

    $feeds = $this->entityTypeManager->getStorage('feeds_feed')->loadMultiple($selection);
    foreach ($feeds as $feed) {
      // As long as the user has access to perform the action on one feed, allow
      // access to the confirm form. Access will be checked again in
      // Drupal\feeds\Form\ActionMultipleForm::submit() in case it has
      // changed in the meantime.
      if ($this->checkFeedAccess($account, $feed)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * Checks access for the given feed.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access check for this account.
   * @param \Drupal\feed\FeedInterface $feed
   *   The feed to check access for.
   */
  abstract protected function checkFeedAccess(AccountInterface $account, FeedInterface $feed);

}
