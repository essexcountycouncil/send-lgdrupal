<?php

namespace Drupal\feeds\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\feeds\FeedStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form for a feed action.
 */
abstract class ActionMultipleForm extends ConfirmFormBase {

  /**
   * A selection of feed ID's.
   *
   * @var array
   */
  protected $feedIds = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The feed storage.
   *
   * @var \Drupal\feeds\FeedStorageInterface
   */
  protected $storage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\feeds\FeedStorageInterface $storage
   *   The feed storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, FeedStorageInterface $storage, AccountInterface $current_user) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $storage;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')->getStorage('feeds_feed'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return static::ACTION;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('feeds.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->feedIds = $this->tempStoreFactory->get(static::ACTION)->get($this->currentUser->id() . ':feeds_feed');
    if (empty($this->feedIds)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    $feeds = $this->storage->loadMultiple($this->feedIds);

    $form['feeds'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function ($feed) {
        return Html::escape($feed->label());
      }, $feeds),
    ];
    return parent::buildForm($form, $form_state);
  }

}
