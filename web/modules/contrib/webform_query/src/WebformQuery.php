<?php

namespace Drupal\webform_query;

use Drupal\Core\Database\Connection;

/**
 * Class WebformQuery.
 */
class WebformQuery {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * Array of sort conditions.
   *
   * @var array
   */
  protected $sort = [];

  /**
   * Array of MIN() and MAX() functions.
   *
   * @var array
   */
  protected $minmax = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Set the ID of the webform to query.
   *
   * @param int $webform_id
   *   The webform ID.
   */
  public function setWebform($webform_id = NULL) {
    if (!is_null($webform_id)) {
      $this->addCondition('webform_id', $webform_id, '=', 'webform_submission');
    }
    return $this;
  }

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
    // Clean up table and field as placeholders can't be used for them.
    $table = $this->connection->escapeTable($table);
    $field = $this->connection->escapeTable($field);

    // Check for webform_id.
    if ($field === 'webform_id') {
      // Check for existing condition at 0.
      if (array_key_exists(0, $this->conditions)) {
        $this->conditions[] = $this->conditions[0];
      }
      $this->conditions[0] = [
        'field' => $field,
        'value' => $value,
        'operator' => $operator,
        'table' => $table,
      ];
    }
    else {

      if (empty($operator)) {
        $operator = '=';
      }

      // Validate opertaor.
      $operator = $this->validateOperator($operator);

      // If operator is good then add the condition.
      if ($operator !== '') {
        $this->conditions[] = [
          'field' => $field,
          'value' => $value,
          'operator' => $operator,
          'table' => $table,
        ];
      }
    }

    return $this;
  }

  /**
   * Set an ORDER BY clause.
   *
   * @param string $field
   *   The field to sort by.
   * @param string $direction
   *   The direction to sort.
   * @param string $table
   *   The table to query.
   */
  public function orderBy($field, $direction = 'ASC', $table = 'webform_submission_data') {
    // Make sure direction is valid.
    $direction = ($direction !== 'ASC') ? 'DESC' : 'ASC';

    $this->sort[] = [
      'field' => $field,
      'direction' => $direction,
      'table' => $table,
    ];

    return $this;

  }

  /**
   * Add MIN or MAX function to the query. Only works with the submission ID.
   * 
   * @param string $function
   *   The function name, MIN or MAX.
   * @param string $table
   *   The table of the field.
   * @param string $group_by
   *   Optional field to group results. E.g. "webform_id".
   * @param array $condition
   *   Optional field to add WHERE condition. ['field', 'operator', 'value']
   */
  public function addMinMax($function, $table = 'webform_submission', $group_by = '', $condition = []) {
    if ($function !== 'MIN') {
      $function = 'MAX';
    }

    $this->minmax[] = [
      'function' => $function,
      'table' => $table,
      'condition' => $condition,
      'group_by' => $group_by,
    ];

    return $this;
  }

  /**
   * Execute the query.
   *
   * @return array
   *   Array of objects with one property: sid
   */
  public function execute() {
    // Return the results.
    return $this->processQuery()->fetchAll();
  }

  /**
   * Process the query and return a database statement.
   *
   * @return Drupal\Core\Database\Statement
   *   Prepared statement.
   */
  public function processQuery() {
    // Generate query elements from the conditions.
    $query_elements = $this->buildQuery();

    // Clear the conditions, sorting and min/max.
    $this->conditions = [];
    $this->sort = [];
    $this->minmax = [];

    // Execute the query.
    return $this->connection->query($query_elements['query'], $query_elements['values']);
  }

  /**
   * Build the query from the conditions.
   */
  public function buildQuery() {
    $query = '';

    foreach ($this->conditions as $key => $condition) {
      $alias = 'table' . $key;

      // Check if querying the results table.
      if ($condition['table'] !== 'webform_submission_data') {
        if (is_array($condition['value'])) {
          $query = ' AND sid IN (SELECT sid FROM {' . $condition['table'] . '} ' . $alias . ' WHERE ' . $alias . '.' . $condition['field'] . ' ' . $condition['operator'] . ' (:' . $condition['field'] . $key . '[]))' . $query;
        }
        else {
          $query = ' AND sid IN (SELECT sid FROM {' . $condition['table'] . '} ' . $alias . ' WHERE ' . $alias . '.' . $condition['field'] . ' ' . $condition['operator'] . ' :' . $condition['field'] . $key . ')' . $query;
        }
      }
      else {
        // Normal condition for a webform submission field.
        if (is_array($condition['value'])) {
          $query .= ' AND sid IN (SELECT sid FROM {webform_submission_data} ' . $alias . ' WHERE ' . $alias . '.name = :' . $condition['field'] . '_name' . $key;
          $query .= ' AND ' . $alias . '.value ' . $condition['operator'] . ' (:' . $condition['field'] . $key . '[]))';
          $values[':' . $condition['field'] . '_name' . $key] = $condition['field'];
        }
        else {
          $query .= ' AND sid IN (SELECT sid FROM {webform_submission_data} ' . $alias . ' WHERE ' . $alias . '.name = :' . $condition['field'] . '_name' . $key;
          $query .= ' AND ' . $alias . '.value ' . $condition['operator'] . ' :' . $condition['field'] . $key . ')';
          $values[':' . $condition['field'] . '_name' . $key] = $condition['field'];
        }
      }
      if (is_array($condition['value'])) {
        $values[':' . $condition['field'] . $key . '[]'] = $condition['value'];
      }
      else {
        $values[':' . $condition['field'] . $key] = $condition['value'];
      }
    }

    // Check for MIN/MAX functions.
    foreach ($this->minmax as $key => $function) {
      // "et": Expression Table.
      $minmax_alias = 'mm' . $key;
      $query .= ' AND sid IN (SELECT ' . $function['function'] . '(' . $minmax_alias . '.sid) FROM {' . $function['table'] . '} ' . $minmax_alias;
      // Check for condition.
      if (!empty($function['condition'])) {
        $minmax_condition = $function['condition'];
        if (empty($minmax_condition[1])) {
          $minmax_condition[1] = '=';
        }
        // Validate operator.
        $operator = $this->validateOperator($minmax_condition[1]);
        $query .= ' WHERE ' . $minmax_condition[0] . ' ' . $operator . ' ' . $minmax_condition[2];
      }
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
      }
      else {
        $ob_prefix = ' ORDER BY';
      }
      // "obt": Order By Table.
      $orderby_alias = 'obt' . $key;

      if ($orderby['table'] === 'webform_submission_data') {
        $query .= $ob_prefix . ' (SELECT ' . $orderby_alias . '.value FROM {webform_submission_data} ' . $orderby_alias . ' WHERE ' . $orderby_alias . '.name=\'' . $orderby['field'] . '\' AND ' . $orderby_alias . '.sid=ws.sid) ' . $orderby['direction'];
      }
      else {
        $query .= $ob_prefix . ' (SELECT ' . $orderby_alias . '.' . $orderby['field'] . ' FROM {' . $orderby['table'] . '} ' . $orderby_alias . ' WHERE ' . $orderby_alias . '.sid=ws.sid) ' . $orderby['direction'];
      }
    }

    $query = substr_replace($query, ' WHERE', 0, 4);
    $query = 'SELECT sid FROM {webform_submission} ws' . $query;

    return ['query' => $query, 'values' => $values];

  }

  /**
   * Perform basic validation of the operator.
   *
   * @param string $operator
   *   The operator to validate.
   *
   * @return string
   *   Return operator or nothing.
   */
  public function validateOperator($operator) {
    if (stripos($operator, 'UNION') !== FALSE || strpbrk($operator, '[-\'"();') !== FALSE) {
      trigger_error('Invalid characters in query operator: ' . $operator, E_USER_ERROR);
      return '';
    }
    return $operator;
  }

}
