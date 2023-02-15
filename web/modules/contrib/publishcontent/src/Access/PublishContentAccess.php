<?php

namespace Drupal\publishcontent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\publishcontent\PublishContentPermissions as Perm;

/**
 * Access class checking the permissions for publishing and unpublishing.
 */
class PublishContentAccess implements AccessInterface {

  /**
   * The account performing the action.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The node that the action is performed on.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The arguments passed to the helper class.
   *
   * @var array
   */
  protected $arguments;

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, NodeInterface $node) {
    $this->account = $account;
    $this->node = $node;
    $this->arguments = ['@type' => $node->bundle()];

    $langcode = $node->language()->getId();
    // We don't want to show the action if the translation doesn't even exist.
    if ($node->isTranslatable() && !$node->hasTranslation($langcode)) {
      return AccessResult::forbidden();
    }

    $action = $node->isPublished() ? 'unpublish' : 'publish';

    if (($action == 'publish'
          && ($this->accessPublishAny()
            || $this->accessPublishEditable()
            || $this->accessPublishAnyType()
            || $this->accessPublishOwnType()
            || $this->accessPublishEditableType())) ||
        ($action == 'unpublish'
          && ($this->accessUnpublishAny()
            || $this->accessUnpublishEditable()
            || $this->accessUnpublishAnyType()
            || $this->accessUnpublishOwnType()
            || $this->accessUnpublishEditableType()))) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();

  }

  /**
   * Helper method to check permission of user.
   */
  public function checkPermission($permission) {
    return $this->account->hasPermission(Perm::getPermission($permission, $this->arguments));
  }

  /**
   * Access publish any content.
   */
  public function accessPublishAny() {
    return $this->account->hasPermission('publish any content');
  }

  /**
   * Access unpublish any content.
   */
  public function accessUnpublishAny() {
    return $this->account->hasPermission('unpublish any content');
  }

  /**
   * Access publish content which you have access to edit for.
   */
  public function accessPublishEditable() {
    return ($this->account->hasPermission('publish editable content') && $this->node->access('update', $this->account));
  }

  /**
   * Access unpublish content which you have access to edit for.
   */
  public function accessUnpublishEditable() {
    return ($this->account->hasPermission('unpublish editable content') && $this->node->access('update', $this->account));
  }

  /**
   * Access publish any content of specified type (bundle).
   */
  public function accessPublishAnyType() {
    return $this->checkPermission(Perm::PUBLISH_ANY_TYPE);
  }

  /**
   * Access unpublish any content of specified type (bundle).
   */
  public function accessUnpublishAnyType() {
    return $this->checkPermission(Perm::UNPUBLISH_ANY_TYPE);
  }

  /**
   * Access publish own content of specified type (bundle).
   */
  public function accessPublishOwnType() {
    return ($this->checkPermission(Perm::PUBLISH_OWN_TYPE) && $this->node->getOwnerId() == $this->account->id());
  }

  /**
   * Access unpublish own content of specified type (bundle).
   */
  public function accessUnpublishOwnType() {
    return ($this->checkPermission(Perm::UNPUBLISH_OWN_TYPE) && $this->node->getOwnerId() == $this->account->id());
  }

  /**
   * Access publish content of specified which you have access to edit for.
   */
  public function accessPublishEditableType() {
    return ($this->checkPermission(Perm::PUBLISH_EDITABLE_TYPE) && $this->node->access('update', $this->account));
  }

  /**
   * Access unpublish content of specified which you have access to edit for.
   */
  public function accessUnpublishEditableType() {
    return ($this->checkPermission(Perm::UNPUBLISH_EDITABLE_TYPE) && $this->node->access('update', $this->account));
  }

}
