<?php

namespace Drupal\localgov_guides\Plugin\Block;

/**
 * Guide contents block.
 *
 * @package Drupal\localgov_guides\Plugin\Block
 *
 * @Block(
 *   id = "localgov_guides_contents",
 *   admin_label = "Guide contents"
 * )
 */
class GuidesContentsBlock extends GuidesAbstractBaseBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->setPages();
    $links = [];

    $options = $this->node->id() == $this->overview->id() ? ['attributes' => ['class' => 'active']] : [];
    $links[] = $this->overview->toLink($this->overview->localgov_guides_section_title->value, 'canonical', $options);
    foreach ($this->guidePages as $guide_node) {
      $options = $this->node->id() == $guide_node->id() ? ['attributes' => ['class' => 'active']] : [];
      $links[] = $guide_node->toLink($guide_node->localgov_guides_section_title->value, 'canonical', $options);
    }

    $build = [];
    $build[] = [
      '#theme' => 'guides_contents_block',
      '#links' => $links,
      '#format' => $this->format,
    ];

    return $build;
  }

}
