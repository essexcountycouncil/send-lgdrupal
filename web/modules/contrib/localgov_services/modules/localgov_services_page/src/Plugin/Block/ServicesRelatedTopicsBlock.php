<?php

namespace Drupal\localgov_services_page\Plugin\Block;

use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\localgov_services\Plugin\Block\ServicesBlockBase;

/**
 * Provides a 'Services Related Topics Block' block.
 *
 * @package Drupal\localgov_services_page\Plugin\Block
 *
 * @Block(
 *   id = "localgov_services_related_topics_block",
 *   admin_label = @Translation("Service page related topics"),
 * )
 */
class ServicesRelatedTopicsBlock extends ServicesBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $links = [];

    if ($this->node->hasField('localgov_topic_classified')) {
      /** @var \Drupal\taxonomy\TermInterface $term_info */
      foreach ($this->node->get('localgov_topic_classified')->getValue() as $term_info) {
        $term = Term::load($term_info['target_id']);

        // Add link only if an actual taxonomy term,
        // deleted topics can return NULL if still present.
        if ($term instanceof TermInterface) {
          $links[] = [
            'title' => $term->label(),
            'url' => $term->toUrl(),
          ];
        }
      }
    }

    if ($links && !$this->hideRelatedTopics()) {
      $build[] = [
        '#theme' => 'services_related_topics_block',
        '#links' => $links,
      ];
    }

    return $build;
  }

  /**
   * Gets the boolean value for localgov_hide_related_topics.
   *
   * @return bool
   *   Should related topics be displayed?
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function hideRelatedTopics() {
    if ($this->node->hasField('localgov_hide_related_topics') && !$this->node->get('localgov_hide_related_topics')->isEmpty()) {
      return (bool) $this->node->get('localgov_hide_related_topics')->first()->getValue()['value'];
    }

    return FALSE;
  }

}
