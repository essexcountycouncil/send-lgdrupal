<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a render element for a table.
 *
 * @FormElement("office_hours_table")
 */
class OfficeHoursTable extends Table {

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $input = parent::valueCallback($element, $input, $form_state);

    return $input;
  }

}
