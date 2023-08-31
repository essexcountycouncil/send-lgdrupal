<?php

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Determine whether to show workflow log on page.
 *
 * @param string $operation
 * @param WebformSubmissionInterface $webform_submission
 *
 * @return boolean
 */
function webform_workflows_element_check_show_log(string $operation, WebformSubmissionInterface $webform_submission): bool {

  if (!Drupal::moduleHandler()->moduleExists('webform_submission_log')) {
    return FALSE;
  }

  $account = Drupal::currentUser();
  $workflowsManager = Drupal::service('webform_workflows_element.manager');

  $webform = $webform_submission->getWebform();
  if ($webform) {
    $workflow_elements = $workflowsManager->getWorkflowElementsForWebform($webform);

    foreach ($workflow_elements as $element) {
      if (!isset($element['#show_log_' . $operation]) || $element['#show_log_' . $operation]) {
        $hasAccess = $workflowsManager->checkUserCanAccessElement($account, $webform, $element, $webform_submission);
        if ($hasAccess) {
          return TRUE;
        }
      }
    }
  }

  return FALSE;
}


/**
 * Get a rendered log table of workflow events for a submission.
 *
 * @param WebformSubmission $webform_submission
 *
 * @return array
 *
 * @throws EntityMalformedException
 */
function webform_workflows_element_get_rendered_log(WebformSubmission $webform_submission): ?array {
  if (!Drupal::moduleHandler()->moduleExists('webform_submission_log')) {
    return NULL;
  }

  $webform = NULL;
  $source_entity = NULL;

  // Entities.
  if (empty($webform) && !empty($webform_submission)) {
    $webform = $webform_submission->getWebform();
  }
  if (empty($source_entity) && !empty($webform_submission)) {
    $source_entity = $webform_submission->getSourceEntity();
  }
  $webform_entity = $webform_submission ?: $webform;

  // Header.
  $header = [];
  // $header['lid'] = ['data' => t('#'), 'field' => 'log.lid', 'sort' => 'desc'];
  if (empty($webform)) {
    $header['webform_id'] = [
      'data' => t('Webform'),
      'field' => 'log.webform_id',
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
  }
  if (empty($webform_submission)) {
    $header['sid'] = ['data' => t('Submission'), 'field' => 'log.sid'];
  }
  $header['timestamp'] = [
    'data' => t('Date'),
    'field' => 'log.timestamp',
    'sort' => 'desc',
    'class' => [RESPONSIVE_PRIORITY_LOW],
  ];
  $header['uid'] = [
    'data' => t('User'),
    'field' => 'user.name',
    'class' => [RESPONSIVE_PRIORITY_LOW],
  ];
  $header['message'] = [
    'data' => t('Message'),
    'field' => 'log.message',
    'class' => [RESPONSIVE_PRIORITY_LOW],
  ];

  // Query.
  $options = ['header' => $header, 'limit' => 50];
  $logs = Drupal::service(
    'webform_submission_log.manager'
  )->loadByEntities($webform_entity, $source_entity, NULL, $options);

  // Rows.
  $rows = [];
  foreach ($logs as $log) {
    if (!in_array($log->operation, [
      'workflow status changed',
      'submission created',
    ])) {
      continue;
    }

    $row = [];

    // $row['lid'] = $log->lid;
    if (empty($webform)) {
      $log_webform = Webform::load($log->webform_id);
      $row['webform_id'] = $log_webform->toLink($log_webform->label(), 'results-log');
    }

    if (empty($webform_submission)) {
      if ($log->sid) {
        $log_webform_submission = WebformSubmission::load($log->sid);
        $row['sid'] = [
          'data' => [
            '#type' => 'link',
            '#title' => $log->sid,
            '#url' => Drupal::service('webform.request')
              ->getUrl($log_webform_submission, $source_entity, 'webform_submission.log'),
          ],
        ];
      }
      else {
        $row['sid'] = '';
      }
    }
    $row['timestamp'] = Drupal::service('date.formatter')
      ->format($log->timestamp, 'short');
    $row['uid'] = [
      'data' => [
        '#theme' => 'username',
        '#account' => User::load($log->uid),
      ],
    ];
    if (isset($log->variables['@transition_id'])) {
      // @todo nicer formatting for these logs using technical reference
      //      $technicalRefs = explode(':', str_replace("Technical reference: ", "", $log->variables['@transition_id']));
      //      $element_id = $technicalRefs[1];
      //      $workflow_plugin_id = $technicalRefs[2];
      //      $transition_id = $technicalRefs[3];
      //      $new_state_id = str_replace(']', '', $technicalRefs[4]);
      //      $old_state_id = isset($technicalRefs[5]) ? str_replace(']', '', $technicalRefs[5]) : '';

      // In the meantime just hide the technical reference
      $log->variables['@transition_id'] = '';
    }

    $message = new TranslatableMarkup($log->message, $log->variables);
    //    244991 244797

    $row['message'] = [
      'data' => [
        '#markup' => $message,
      ],
    ];

    $rows[] = $row;
  }

  $build['table'] = [
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#sticky' => TRUE,
    '#empty' => t('No log messages available.'),
  ];

  /* $build['pager'] = ['#type' => 'pager']; */

  return [
    'log_fieldset' => [
      '#type' => 'details',
      '#title' => t('Workflow log'),
      '#weight' => -18,
      'log' => [
        '#type' => 'markup',
        '#group' => 'log_fieldset',
        '#markup' => Drupal::service('renderer')->render($build),
      ],
    ],

  ];
}
