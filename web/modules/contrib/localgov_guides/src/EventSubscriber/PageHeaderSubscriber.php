<?php

namespace Drupal\localgov_guides\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\localgov_core\Event\PageHeaderDisplayEvent;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set page title.
 *
 * @package Drupal\localgov_guides\EventSubscriber
 */
class PageHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PageHeaderDisplayEvent::EVENT_NAME => ['setPageHeader', 0],
    ];
  }

  /**
   * Set page title and lede.
   */
  public function setPageHeader(PageHeaderDisplayEvent $event) {

    $node = $event->getEntity();

    if (!$node instanceof Node) {
      return;
    }

    if ($node->bundle() !== 'localgov_guides_page') {
      return;
    }

    $overview = $node->localgov_guides_parent->entity;
    if (!empty($overview)) {
      $event->setTitle($overview->getTitle());
      if ($overview->get('body')->summary) {
        $event->setLede([
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $overview->get('body')->summary,
        ]);
      }
      $event->setCacheTags(Cache::mergeTags($node->getCacheTags(), $overview->getCacheTags()));
    }
    else {
      $event->setLede('');
    }
  }

}
