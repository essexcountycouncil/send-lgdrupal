<?php

namespace Drupal\webform_workflows_element;

use Drupal;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\webform\WebformSubmissionListBuilder;

/**
 * Provides a list controller for webform submission entity.
 *
 * @ingroup webform
 */
class WebformSubmissionWorkflowListBuilder extends WebformSubmissionListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getQuery($keys = '', $state = '', $source_entity = ''): QueryInterface {
    $query = parent::getQuery();

    $queryStrings = Drupal::request()->query->all();

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = $this->getStorage();

    $data = [
      'query' => $query,
      'submission_storage' => $submission_storage,
    ];
    webform_workflows_element_webform_better_results_query_alter($data, $queryStrings, $this->webform);
    return $data['query'];
  }

}
