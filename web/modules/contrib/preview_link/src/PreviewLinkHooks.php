<?php

namespace Drupal\preview_link;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal hooks.
 */
class PreviewLinkHooks implements ContainerInjectionInterface {

  /**
   * Preview link storage.
   *
   * @var \Drupal\preview_link\PreviewLinkStorageInterface
   */
  protected $previewLinkStorage;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Calculates link expiry time.
   *
   * @var \Drupal\preview_link\LinkExpiry
   */
  protected $linkExpiry;

  /**
   * PreviewLinkHooks constructor.
   *
   * @param \Drupal\preview_link\PreviewLinkStorageInterface $previewLinkStorage
   *   Preview link storage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   * @param \Drupal\preview_link\LinkExpiry $linkExpiry
   *   Calculates link expiry time.
   */
  public function __construct(PreviewLinkStorageInterface $previewLinkStorage, TimeInterface $time, LinkExpiry $linkExpiry) {
    $this->previewLinkStorage = $previewLinkStorage;
    $this->time = $time;
    $this->linkExpiry = $linkExpiry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('preview_link'),
      $container->get('datetime.time'),
      $container->get('preview_link.link_expiry')
    );
  }

  /**
   * Implements hook_cron().
   *
   * @see \preview_link_cron()
   */
  public function cron() {
    $expireBeforeTime = $this->time->getRequestTime() - $this->linkExpiry->getLifetime();
    $ids = $this->previewLinkStorage->getQuery()
      ->condition('generated_timestamp', $expireBeforeTime, '<')
      ->execute();

    // If there are no expired links then nothing to do.
    if (!count($ids)) {
      return;
    }

    $previewLinks = $this->previewLinkStorage->loadMultiple($ids);
    // Simply delete the preview links. A new one will be regenerated at a later
    // date as required.
    $this->previewLinkStorage->delete($previewLinks);
  }

}
