<?php

namespace Drupal\webform_workflows_element_maestro\Plugin\EngineTasks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\WebformSubmission;

/**
 * MaestroTaskTrait.
 *
 * Provides base task parameters and methods.
 * Includes the processID and queueID properties and methods for base task implementation.
 *
 * @ingroup maestro
 */
trait MaestroWebformWorkflowsTrait {

  /**
   * Get webform submission for queue item.
   *
   * @param string $queueID
   *
   * @return WebformSubmission
   */
  public static function getSubmission($queueID) {
    $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
    $entity_id = MaestroEngine::getEntityIdentiferByUniqueID($processID, 'submission');
    $submission = WebformSubmission::load($entity_id);
    return $submission;
  }
}
