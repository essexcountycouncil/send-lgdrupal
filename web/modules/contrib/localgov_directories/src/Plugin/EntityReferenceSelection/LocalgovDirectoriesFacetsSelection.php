<?php

namespace Drupal\localgov_directories\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin description.
 *
 * @EntityReferenceSelection(
 *   id = "localgov_directories_facets_selection",
 *   label = @Translation("Directories facets selection"),
 *   group = "localgov_directories_facets_selection",
 *   entity_types = {"localgov_directories_facets"},
 *   weight = 0
 * )
 */
class LocalgovDirectoriesFacetsSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['target_bundles']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    return $query;
  }

}
