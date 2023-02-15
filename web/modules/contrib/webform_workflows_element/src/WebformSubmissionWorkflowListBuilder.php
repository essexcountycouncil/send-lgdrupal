<?php

namespace Drupal\webform_workflows_element;

use Drupal;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionListBuilder;

/**
 * Provides a list controller for webform submission entity.
 *
 * @ingroup webform
 */
class WebformSubmissionWorkflowListBuilder extends WebformSubmissionListBuilder {

  /**
   * Initialize WebformSubmissionListBuilder object.
   */
  protected function initialize() {
    parent::initialize();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTotal($keys = '', $state = '', $source_entity = '') {
    $ids = $this->getQuery($keys, $state, $source_entity)
      ->execute();
    return count($this->filterIdsByState($ids));
  }

  /**
   * Filter out any submission ids not at the given state.
   *
   * @param array $ids
   * @param string|null $state
   *
   * @return array
   * @todo make this work for multiple workflow elements
   */
  protected function filterIdsByState(array $ids, string $state = NULL): array {
    $workflowFields = $this->getWorkflowFields();

    if (count($workflowFields) == 0) {
      return $ids;
    }

    $finalIds = [];
    foreach ($ids as $id) {
      foreach ($this->getWorkflowFields() as $workflowElement => $filterValue) {
        $submission = WebformSubmission::load($id);
        if ($this->submissionHasState($submission, $workflowElement, $filterValue)) {
          $finalIds[] = $id;
        }
      }
    }
    return $finalIds;
  }

  /**
   * Load workflow fields to filter by, from query string.
   *
   * @return array
   */
  protected function getWorkflowFields(): array {
    $workflowFields = [];
    $query = Drupal::request()->query->all();
    foreach ($query as $key => $value) {
      if (strpos($key, 'workflow-') === 0) {
        $workflowFields[$key] = $value;
      }
    }
    return $workflowFields;
  }

  /**
   * Check if submission is at a current state for an element.
   *
   * @param WebformSubmissionInterface $submission
   * @param mixed $workflowElement
   * @param string|null $state
   *
   * @return bool
   *   TRUE if submission is at current state for element
   */
  protected function submissionHasState(WebformSubmissionInterface $submission, $workflowElement, string $state = NULL): bool {
    if (!$state || $state == '') {
      return FALSE;
    }
    $submissionValue = $submission->getElementData(str_replace('workflow-', '', $workflowElement));
    return $submissionValue && $submissionValue['workflow_state'] == $state;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    // Only disable pagination if currently filtering by status
    $workflowFields = $this->getWorkflowFields();
    if (count($workflowFields) == 0) {
      return parent::getEntityIds();
    }

    $this->limit = FALSE;

    $ids = parent::getEntityIds();

    $finalIds = $this->filterIdsByState($ids);

    if (count($ids) != count($finalIds)) {
      $this->limit = FALSE;

      // Temporarily disable pagination for filtering by state.
      // @todo Work out how to properly do this.

      $this->total = count($finalIds);

      $this->pagerManager->createPager($this->total, $this->total);

      // $pager = $this->pagerManager->getPager();
      // $pager->setTotalPages($this->total);
      // $this->pagerManager->setPager($pager);
      // $query = $this->getQuery($this->keys, $this->state, $this->sourceEntityTypeId);
      // $query->pager(0);

      // $this->pagerManager->createPager($this->total, $this->limit);
    }

    return $finalIds;
  }

}
