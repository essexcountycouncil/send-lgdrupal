<?php

namespace Drupal\send_directory_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckController extends ControllerBase {

  /**
   * Update the "Provider Last Checked" value on the node.
   */
  public function approve(NodeInterface $node) {
    $node->set('field_provider_last_checked', date('Y-m-d\\TH:i:s'));
    $node->save();
    \Drupal::messenger()->addMessage(t('Thank you for letting us know this listing is still up to date.'), 'status');
    return $this->pageRedirect($node);
  }

  protected function pageRedirect(NodeInterface $node): Response {
    return new RedirectResponse(
      Url::fromRoute(
        'entity.node.canonical',
        [
          'node' => $node->id(),
        ],
      )->toString(),
    );
  }

}
