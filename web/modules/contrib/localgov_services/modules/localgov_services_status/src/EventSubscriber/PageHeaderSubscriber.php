<?php

namespace Drupal\localgov_services_status\EventSubscriber;

use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\localgov_core\Event\PageHeaderDisplayEvent;

/**
 * Hide page title.
 *
 * @package Drupal\localgov_services_status\EventSubscriber
 */
class PageHeaderSubscriber implements EventSubscriberInterface {

  /**
   * Current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   Current path service.
   */
  public function __construct(CurrentPathStack $current_path_stack) {
    $this->currentPathStack = $current_path_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PageHeaderDisplayEvent::EVENT_NAME => ['setPageHeader', 0],
    ];
  }

  /**
   * Hide page header for the /service-status page.
   */
  public function setPageHeader(PageHeaderDisplayEvent $event) {

    // Hide page header block.
    $current_path = $this->currentPathStack->getPath();
    if ($current_path == '/service-status') {
      $event->setVisibility(FALSE);
    }
  }

}
