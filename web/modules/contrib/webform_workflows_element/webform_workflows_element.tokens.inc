<?php

/**
 * @file
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Implements hook_token_info().
 */
function webform_workflows_element_token_info(): array {
  $types = [];
  $types['webform_workflow'] = [
    'name' => t('Webform submissions - workflows'),
    'description' => t('Tokens related to webform submission workflows.'),
    // 'needs-data' => 'webform_submission',
  ];

  $webform_submission = [];

  // webform_workflow:transition-url:{element_id}:{transition_id}
  $webform_submission['transition-url'] = [
    'name' => t('Workflow transition URL'),
    'description' => t('The URL that can used to access the webform submission to update the workflow to this transition - user would need to log in if not already logged in.'),
    'type' => 'url',
    'dynamic' => TRUE,
  ];

  $webform_submission['transition-link'] = [
    'name' => t('Workflow transition link with name of transition'),
    'description' => t('Link to access the webform submission to update the workflow to this transition without logging in. The webform must be configured to allow users to update a submission using a secure token.'),
    'dynamic' => TRUE,
  ];

  $webform_submission['transition-url-secure-token'] = [
    'name' => t('Workflow transition URL via secure token'),
    'description' => t('The URL that can used to access the webform submission to update the workflow to this transition without logging in. The webform must be configured to allow users to update a submission using a secure token.'),
    'type' => 'url',
    'dynamic' => TRUE,
  ];

  $webform_submission['transition-link-secure-token'] = [
    'name' => t('Workflow transition link with name of transition'),
    'description' => t('Link to access the webform submission to update the workflow to this transition without logging in. The webform must be configured to allow users to update a submission using a secure token.'),
    'dynamic' => TRUE,
  ];

  return [
    'types' => $types,
    'tokens' => [
      'webform_workflow' => $webform_submission,
    ],
  ];
}


/**
 * Implements hook_tokens().
 */
function webform_workflows_element_tokens(
  $type,
  $tokens,
  array $data,
  array $options,
  BubbleableMetadata $bubbleable_metadata
): array {

  $replacements = [];
  if ($type === 'webform_workflow' && !empty($data['webform_submission'])) {
    // Adding webform submission, webform, source entity to bubbleable meta.
    // This reduces code duplication and easier to track.
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];
    $bubbleable_metadata->addCacheableDependency($webform_submission);

    $workflowsManager = Drupal::service('webform_workflows_element.manager');

    foreach ($tokens as $name => $original) {
      $values = explode(':', $name);
      $first_value = reset($values);
      switch ($first_value) {
        case 'transition-url':
        case 'transition-url-secure-token':
          $replacements[$original] = _webform_workflows_element_token_get_url_to_transition($name, $options, $webform_submission);
          break;


        case 'transition-link':
        case 'transition-link-secure-token':
          // Get label for transition:
          $keys = explode(':', $name);
          $element_id = $keys[1];
          $transition_id = $keys[2];
          $element = $webform_submission->getWebform()
            ->getElementDecoded($element_id);
          $workflowType = $workflowsManager->getWorkflowTypeFromElement($element);
          $transition = $workflowType->getTransition($transition_id);
          $label = $transition->label();

          // Get URL:
          $url = _webform_workflows_element_token_get_url_to_transition($name, $options, $webform_submission);

          // Return basic link:
          $replacements[$original] = Markup::create('<a href="' . $url . '">' . $label . '</a>');
          break;

        default:
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Convert a link token name to a URL.
 *
 * @throws \Drupal\Core\Entity\EntityMalformedException
 */
function _webform_workflows_element_token_get_url_to_transition(string $name, array $options, WebformSubmissionInterface $webform_submission) {
  // webform-workflows:transition-link:{element_id}:{transition_id}
  $keys = explode(':', $name);
  $token = $keys[0];
  $element_id = $keys[1];
  $transition_id = $keys[2];

  // Get base URL:
  if ($token == 'transition-url-secure-token') {
    $url = $webform_submission->getTokenUrl('canonical');
  }
  else {
    $url = $webform_submission->toUrl('canonical');
  }

  $url->setAbsolute(TRUE);

  // Set query string:
  $url->setOption('query', ($url->getOption('query') ?? []) + [
      'workflow_element' => $element_id,
      'transition' => $transition_id,
    ]);

  return $url->toString();
}
