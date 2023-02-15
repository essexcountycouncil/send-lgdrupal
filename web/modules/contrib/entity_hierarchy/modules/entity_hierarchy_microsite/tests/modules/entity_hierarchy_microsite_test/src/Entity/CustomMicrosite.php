<?php

declare(strict_types=1);

namespace Drupal\entity_hierarchy_microsite_test\Entity;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;
use Drupal\node\Entity\Node as DrupalNode;
use PNX\NestedSet\Node;

/**
 * Defines a class for a custom microsite entity.
 */
final class CustomMicrosite extends Microsite {

  /**
   * {@inheritdoc}
   */
  public function modifyMenuPluginDefinition(Node $treeNode, DrupalNode $node, array $definition, Node $homeNode): array {
    if ($treeNode->getDepth() > \Drupal::state()->get('entity_hierarchy_microsite_max_depth', 100)) {
      return [];
    }
    return parent::modifyMenuPluginDefinition($treeNode, $node, $definition, $homeNode);
  }

}
