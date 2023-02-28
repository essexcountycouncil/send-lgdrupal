<?php

namespace Drupal\feeds_tamper\Plugin\Derivative;

use Drupal\feeds\Plugin\Derivative\ExtraLinks as ExtraLinksBase;

/**
 * Adds extra menu links to the feed type menu.
 */
class ExtraLinks extends ExtraLinksBase {

  /**
   * {@inheritdoc}
   */
  protected function getRoutes(): array {
    return [
      'entity.feeds_feed_type.tamper' => [
        'title' => $this->t('Tamper'),
        'weight' => -9,
      ],
    ];
  }

}
