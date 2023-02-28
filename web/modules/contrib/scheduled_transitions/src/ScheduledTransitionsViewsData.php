<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions;

use Drupal\scheduled_transitions\Plugin\views\field\ScheduledTransitionFromStateViewsField;
use Drupal\scheduled_transitions\Plugin\views\field\ScheduledTransitionRevisionLinkField;
use Drupal\scheduled_transitions\Plugin\views\field\ScheduledTransitionToStateViewsField;
use Drupal\views\EntityViewsData;

/**
 * Provides views data for scheduled transitions.
 */
class ScheduledTransitionsViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();

    // Create a new revision link.
    $data['scheduled_transition']['entity_revision_link'] = [
      'field' => [
        'id' => ScheduledTransitionRevisionLinkField::PLUGIN_ID,
      ],
      'title' => $this->t('Revision link'),
      'help' => $this->t('Link to the scheduled entity revision.'),
    ];

    // Create a new FROM field.
    $data['scheduled_transition']['state_from'] = [
      'field' => [
        'id' => ScheduledTransitionFromStateViewsField::PLUGIN_ID,
      ],
      'title' => $this->t('Previous State'),
      'help' => $this->t('The previous state of the content.'),
    ];

    // Override the TO moderation state field.
    $data['scheduled_transition']['moderation_state']['title'] = $this->t('New State');
    $data['scheduled_transition']['moderation_state']['field']['id'] = ScheduledTransitionToStateViewsField::PLUGIN_ID;

    return $data;
  }

}
