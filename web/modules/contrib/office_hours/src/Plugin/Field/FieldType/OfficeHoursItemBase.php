<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Implementation of the 'office_hours' field settings.
 */
class OfficeHoursItemBase extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'day' => [
          'type' => 'int',
          'description' => 'Day',
          'not null' => FALSE,
        ],
        'starthours' => [
          'type' => 'int',
          'description' => 'From',
          'not null' => FALSE,
        ],
        'endhours' => [
          'type' => 'int',
          'description' => 'To',
          'not null' => FALSE,
        ],
        'comment' => [
          'type' => 'varchar',
          'description' => 'Comment',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $labels = OfficeHoursItem::getPropertyLabels('data');

    $properties['day'] = DataDefinition::create('integer')
      ->setLabel($labels['day']['data'])
      // ->setRequired(TRUE) // @todo Set required.
      ->setDescription("Stores the day of the week's numeric representation (0=Sun, 6=Sat)");
    $properties['all_day'] = DataDefinition::create('boolean')
      ->setLabel($labels['all_day']['data'])
      // ->setComputed(TRUE) // Setting this generates an error in formatter.
      ->setDescription("Indicator that display whether the entity is open 24 hours on this day.");
    $properties['starthours'] = DataDefinition::create('integer')
      ->setLabel($labels['from']['data'])
      ->setDescription("Stores the start hours value");
    $properties['endhours'] = DataDefinition::create('integer')
      ->setLabel($labels['to']['data'])
      ->setDescription("Stores the end hours value");
    $properties['comment'] = DataDefinition::create('string')
      ->setLabel($labels['comment']['data'])
      ->addConstraint('Length', ['max' => 255])
      ->setDescription("Stores the comment");

    return $properties;
  }

  /**
   * Returns a unified set of translated labels for the widget.
   *
   * @param string $parent
   *   The key, where the label must be stored.
   * @param array $field_settings
   *   The field settings, influencing the result.
   *
   * @return array
   *   The keyed set of translated labels.
   */
  public static function getPropertyLabels($parent, array $field_settings = []) {
    /*
     * Hmm, from where to take the titles...
     * - office_hours.schema.yml does not contain semi-computed all_day,
     *   and we should not use it, since it does not contain the 'context';
     * - OfficeHoursItem::schema() does not contain semi-computed all_day,
     *   and we should not use it, since it does not contain the 'context';
     * - OfficeHoursItem::propertyDefinitions() has it all, and
     *   it contains context-aware translations of 'From' , 'To',
     *   except when 'all_day' is set to 'computed'.
     */

    // Added for propertyDefinition.
    if ($field_settings['season'] ?? FALSE) {
      $properties['season'][$parent] = t('Season name');
    }
    $properties['day'][$parent] = t('Day');
    $properties['all_day'][$parent] = t('All day');
    if (!($field_settings['all_day'] ?? TRUE)) {
      $properties['all_day']['class'] = 'hidden';
    }
    $properties['from'][$parent]
      = t('From', [], ['context' => 'A point in time']);
    $properties['to'][$parent]
      = t('To', [], ['context' => 'A point in time']);
    $properties['comment'][$parent] = t('Comment');
    if (!($field_settings['comment'] ?? TRUE)) {
      $properties['comment']['class'] = 'hidden';
    }

    // Added for Widget.
    $properties['operations'][$parent] = t('Operations');

    // Added for FormatterTable.
    if (($field_settings['slots'] ?? FALSE)) {
      $properties['slots'][$parent] = t('Time slot');
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $defaultStorageSettings = [
      'time_format' => 'G',
      'element_type' => 'office_hours_datelist',
      'increment' => 30,
      'required_start' => FALSE,
      'required_end' => FALSE,
      'limit_start' => '',
      'limit_end' => '',
      'all_day' => FALSE,
      'exceptions' => TRUE,
      'seasons' => FALSE,
      'comment' => 1,
      'valhrs' => FALSE,
      'cardinality_per_day' => 2,
    ] + parent::defaultStorageSettings();

    return $defaultStorageSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    $settings = $this->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSettings();

    return parent::storageSettingsForm($form, $form_state, $has_data)
    + $this->getStorageSettingsElement($settings);
  }

  /**
   * Returns a form for the storage-level settings.
   *
   * Isolated as a static form, to be invoked from both class OfficeHoursItem
   * and class WebFormOfficeHours (extends WebformCompositeBase).
   *
   * @param array $settings
   *   The field settings.
   *
   * @return array
   *   The form definition for the field settings.
   */
  public static function getStorageSettingsElement(array $settings) {

    // Get a formatted list of valid hours values.
    $hours = OfficeHoursDateHelper::hours('H', FALSE);
    foreach ($hours as &$hour) {
      if (!empty($hour)) {
        $hrs = OfficeHoursDateHelper::format($hour . '00', 'H:i');
        $ampm = OfficeHoursDateHelper::format($hour . '00', 'g:i a');
        $hour = "$hrs ($ampm)";
      }
    }

    $element['#element_validate'] = [
      [static::class, 'validateStorageSettings'],
    ];
    $description = t(
      'The maximum number of time slots, that are allowed per day.
      <br/><strong> Warning! Lowering this setting after data has been created
      could result in the loss of data! </strong><br/> Be careful when using
      more then 2 slots per day, since not all external services (like Google
      Places) support this.');
    $element['cardinality_per_day'] = [
      '#type' => 'select',
      '#title' => t('Number of time slots per day'),
      '#options' => array_combine(range(1, 12), range(1, 12)),
      '#default_value' => $settings['cardinality_per_day'],
      '#description' => $description,
    ];

    $element['element_type'] = [
      '#type' => 'select',
      '#title' => t('Time element type'),
      '#description' => t('Select the widget type for selecting the time.'),
      '#options' => [
        'office_hours_datelist' => t('Select list'),
        'office_hours_datetime' => t('HTML5 time input'),
      ],
      '#default_value' => $settings['element_type'],
    ];
    // @todo D8 Move to widget settings. Align with DateTimeDatelistWidget.
    $element['time_format'] = [
      '#type' => 'select',
      '#title' => t('Time notation'),
      '#options' => [
        'G' => t('24 hour time @example', ['@example' => '(9:00)']),
        'H' => t('24 hour time @example', ['@example' => '(09:00)']),
        'g' => t('12 hour time @example', ['@example' => '(9:00 am)']),
        'h' => t('12 hour time @example', ['@example' => '(09:00 am)']),
      ],
      '#default_value' => $settings['time_format'],
      '#required' => FALSE,
      // @todo Add #states to disable/hide/set value to 'H'.
      '#description' => t('Format of the time in the widget.
        Please note that HTML5 time input only supports 09:00 format'),
    ];
    // @todo D8 Align with DateTimeDatelistWidget.
    $element['increment'] = [
      '#type' => 'select',
      '#title' => t('Time increments'),
      '#default_value' => $settings['increment'],
      '#options' => [
        1 => t('1 minute'),
        5 => t('5 minute'),
        15 => t('15 minute'),
        30 => t('30 minute'),
        60 => t('60 minute'),
      ],
      '#required' => FALSE,
      '#description' => t('Restrict the input to fixed fractions of an hour.'),
    ];

    $element['all_day'] = [
      '#type' => 'checkbox',
      '#title' => t("Allow 'all day' situations"),
      '#required' => FALSE,
      '#default_value' => $settings['all_day'],
      '#description' => t('Adds a checkbox to the widget. When this checkbox is
        set by the user, the start and end hours will be disabled, and
        the time slot will be regarded as \'All day open\'.'),
    ];
    $element['comment'] = [
      '#type' => 'select',
      '#title' => t('Allow a comment per time slot'),
      '#required' => FALSE,
      '#default_value' => $settings['comment'],
      '#options' => [
        0 => t('No comments allowed'),
        1 => t('Allow comments (HTML tags possible)'),
        2 => t('Allow translatable comments (no HTML)'),
      ],
    ];
    $element['exceptions'] = [
      '#type' => 'checkbox',
      '#title' => t("Allow exception days"),
      '#required' => FALSE,
      '#default_value' => $settings['exceptions'],
      '#description' => t("Allows to register exception days, like
        'Closed on Christmas'. This requires the Extended Weekday Widget."),
    ];
    $element['seasons'] = [
      '#type' => 'checkbox',
      '#title' => t("Allow seasons"),
      '#required' => FALSE,
      '#default_value' => $settings['seasons'],
      '#description' => t("Allows to register weekly opening hours for seasons,
        like 'Summer 2025' or an infinite 'Summer' season.
        This requires the Extended Weekday Widget."),
    ];
    $element['valhrs'] = [
      '#type' => 'checkbox',
      '#title' => t('Validate hours'),
      '#required' => FALSE,
      '#default_value' => $settings['valhrs'],
      '#description' => t('Assure that endhours are later then starthours.
        Please note that this will work as long as both hours are set and
        the opening hours are not through midnight.'),
    ];
    $element['required_start'] = [
      '#type' => 'checkbox',
      '#title' => t('Require Start time'),
      '#default_value' => $settings['required_start'],
    ];
    $element['required_end'] = [
      '#type' => 'checkbox',
      '#title' => t('Require End time'),
      '#default_value' => $settings['required_end'],
    ];
    $element['limit_start'] = [
      '#type' => 'select',
      '#title' => t('Limit hours - from'),
      '#description' => t('Restrict the hours available - select options will start from this hour.'),
      '#default_value' => $settings['limit_start'],
      '#options' => $hours,
    ];
    $element['limit_end'] = [
      '#type' => 'select',
      '#title' => t('Limit hours - until'),
      '#description' => t('Restrict the hours available - select options
         will end at this hour. You may leave \'until\' time empty.
         Use \'00:00\' for closing at midnight.'),
      '#default_value' => $settings['limit_end'],
      '#options' => $hours,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $value = [
      'day' => mt_rand(0, 6),
      'starthours' => mt_rand(00, 23) * 100,
      'endhours' => mt_rand(00, 23) * 100,
      'comment' => mt_rand(0, 1) ? 'additional text' : '',
    ];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = parent::getValue();

    if (!$value) {
      $this->applyDefaultValue();
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Apply the default value of all properties.
    // parent::applyDefaultValue($notify);.
    $this->setValue([], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(OfficeHoursItem $a, OfficeHoursItem $b) {
    // Sort the entities using the entity class's sort() method.
    $a_day = $a->day;
    $b_day = $b->day;
    if ($a_day < $b_day) {
      return -1;
    }
    if ($a_day > $b_day) {
      return +1;
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = [];
    // @todo When adding parent::getConstraints(), only English is allowed...
    // $constraints = parent::getConstraints();
    $max_length = $this->getSetting('max_length');
    if ($max_length) {
      $constraint_manager = \Drupal::typedDataManager()
        ->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length,
            ]),
          ],
        ],
      ]);
    }
    return $constraints;
  }

  /**
   * Implements the #element_validate callback for storageSettingsForm().
   *
   * Verifies the office hours limits.
   * "Please note that this will work as long as the opening hours
   * "are not through midnight.
   * "You may leave 'until' time empty. Use '00:00' for closing at midnight."
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function validateStorageSettings(array $element, FormStateInterface &$form_state) {
    if (!empty($element['limit_end']['#value']) &&
      $element['limit_end']['#value'] < $element['limit_start']['#value']) {
      $form_state->setError($element['limit_start'], t('%start is later then %end.', [
        '%start' => $element['limit_start']['#title'],
        '%end' => $element['limit_end']['#title'],
      ]));
    }
  }

}
