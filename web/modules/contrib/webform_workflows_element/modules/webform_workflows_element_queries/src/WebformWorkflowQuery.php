<?php

namespace Drupal\webform_workflows_element_queries;

use Drupal\Core\Database\Connection;
use Drupal\webform_query\WebformQuery;

/**
 * Class WebformQuery.
 */
class WebformWorkflowsQuery extends WebformQuery {

  /**
   * Set a query condition.
   *
   * @param string $field
   *   Field name.
   * @param mixed $value
   *   Value to compare.
   * @param mixed $operator
   *   Operator.
   * @param string $table
   *   The table to query.
   *
   * @return $this
   */
  public function addCondition($field, $value = NULL, $operator = '=', $table = 'webform_submission_data') {
    parent::addCondition($field, $value, $operator, $table);

    $property = '';
    if (!strstr($field, ':')) {
      return $this;
    }

    $values = explode(':', $field);
    $base_field = $values[0];
    $property = $values[1];

    $property = $this->connection->escapeTable($property);
    $base_field = $this->connection->escapeTable($base_field);

    foreach ($this->conditions as $id => $condition) {
      if ($condition['field'] == $field) {
        $condition['field'] = 'base_field';
        $condition['property'] = $property;
      }
      $this->conditions[$id] = $condition;
    }

    return $this;
  }

  /**
   * Build the query from the conditions.
   */
  public function buildQuery() {
    // @todo allow filtering by property (for composite elements) as well as field.

    $query = '';

    foreach ($this->conditions as $key => $condition) {
      $alias = 'table' . $key;

      // Check if querying the results table.
      if ($condition['table'] !== 'webform_submission_data') {
        if (is_array($condition['value'])) {
          $query = ' AND sid IN (SELECT sid FROM {' . $condition['table'] . '} ' . $alias . ' WHERE ' . $alias . '.' . $condition['field'] . ' ' . $condition['operator'] . ' (:' . $condition['field'] . $key . '[]))' . $query;
        } else {
          $query = ' AND sid IN (SELECT sid FROM {' . $condition['table'] . '} ' . $alias . ' WHERE ' . $alias . '.' . $condition['field'] . ' ' . $condition['operator'] . ' :' . $condition['field'] . $key . ')' . $query;
        }
      } else {
        // Normal condition for a webform submission field.
        if (is_array($condition['value'])) {
          $query .= ' AND sid IN (SELECT sid FROM {webform_submission_data} ' . $alias . ' WHERE ' . $alias . '.name = :' . $condition['field'] . '_name' . $key;
          $query .= ' AND ' . $alias . '.value ' . $condition['operator'] . ' (:' . $condition['field'] . $key . '[]))';
          $values[':' . $condition['field'] . '_name' . $key] = $condition['field'];
        } else {
          $query .= ' AND sid IN (SELECT sid FROM {webform_submission_data} ' . $alias . ' WHERE ' . $alias . '.name = :' . $condition['field'] . '_name' . $key;
          $query .= ' AND ' . $alias . '.value ' . $condition['operator'] . ' :' . $condition['field'] . $key . ')';
          $values[':' . $condition['field'] . '_name' . $key] = $condition['field'];
        }
      }
      if (is_array($condition['value'])) {
        $values[':' . $condition['field'] . $key . '[]'] = $condition['value'];
      } else {
        $values[':' . $condition['field'] . $key] = $condition['value'];
      }
    }

    // Check for MIN/MAX functions.
    foreach ($this->minmax as $key => $function) {
      // "et": Expression Table.
      $minmax_alias = 'mm' . $key;
      $query .= ' AND sid IN (SELECT ' . $function['function'] . '(' . $minmax_alias . '.sid) FROM {' . $function['table'] . '} ' . $minmax_alias;
      if ($function['group_by'] !== '') {
        $query .= ' GROUP BY ' . $function['group_by'];
      }
      $query .= ')';
    }

    // Check for sort criteria.
    foreach ($this->sort as $key => $orderby) {
      // Add comma separator for for additional ORDER BY.
      if ($key > 0) {
        $query .= ',';
        $ob_prefix = '';
      } else {
        $ob_prefix = ' ORDER BY';
      }
      // "obt": Order By Table.
      $orderby_alias = 'obt' . $key;

      if ($orderby['table'] === 'webform_submission_data') {
        $query .= $ob_prefix . ' (SELECT ' . $orderby_alias . '.value FROM {webform_submission_data} ' . $orderby_alias . ' WHERE ' . $orderby_alias . '.name=\'' . $orderby['field'] . '\' AND ' . $orderby_alias . '.sid=ws.sid) ' . $orderby['direction'];
      } else {
        $query .= $ob_prefix . ' (SELECT ' . $orderby_alias . '.' . $orderby['field'] . ' FROM {' . $orderby['table'] . '} ' . $orderby_alias . ' WHERE ' . $orderby_alias . '.sid=ws.sid) ' . $orderby['direction'];
      }
    }

    $query = substr_replace($query, ' WHERE', 0, 4);
    $query = 'SELECT sid FROM {webform_submission} ws' . $query;

    return ['query' => $query, 'values' => $values];
  }
}
