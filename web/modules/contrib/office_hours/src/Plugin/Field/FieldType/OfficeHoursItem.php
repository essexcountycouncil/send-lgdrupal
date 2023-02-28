<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\office_hours\Element\OfficeHoursDatetime;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours",
 *   label = @Translation("Office hours"),
 *   description = @Translation("This field stores weekly 'office hours' or 'opening hours' in the database."),
 *   default_widget = "office_hours_default",
 *   default_formatter = "office_hours",
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 * )
 */
class OfficeHoursItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'day' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
        'starthours' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
        'endhours' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
        'comment' => [
          'type' => 'varchar',
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
    $properties['day'] = DataDefinition::create('integer')
      ->setLabel(t('Day'))
      ->setDescription("Stores the day of the week's numeric representation (0=Sun, 6=Sat)");
    $properties['starthours'] = DataDefinition::create('integer')
      ->setLabel(t('Start hours'))
      ->setDescription("Stores the start hours value");
    $properties['endhours'] = DataDefinition::create('integer')
      ->setLabel(t('End hours'))
      ->setDescription("Stores the end hours value");
    $properties['comment'] = DataDefinition::create('string')
      ->setLabel(t('Comment'))
      ->addConstraint('Length', ['max' => 255])
      ->setDescription("Stores the comment");

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
      [static::class, 'validateOfficeHoursSettings'],
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

    // @todo D8 Move to widget settings. Align with DateTimeDatelistWidget.
    $element['time_format'] = [
      '#type' => 'select',
      '#title' => t('Time notation'),
      '#options' => [
        'G' => t('24 hour time @example', ['@example' => '(9:00)']),
        'H' => t('24 hour time @example', ['@example' => '(09:00)']),
        'g' => t('12 hour time @example', ['@example' => '9:00 am)']),
        'h' => t('12 hour time @example', ['@example' => '(09:00 am)']),
      ],
      '#default_value' => $settings['time_format'],
      '#required' => FALSE,
      '#description' => t('Format of the time in the widget.'),
    ];
    $element['element_type'] = [
      '#type' => 'select',
      '#title' => t('Time element type'),
      '#description' => t('Select the widget type for selecting the time.'),
      '#options' => [
        'office_hours_datelist' => 'Select list',
        'office_hours_datetime' => 'HTML5 time input',
      ],
      '#default_value' => $settings['element_type'],
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
   * Determines whether the item is a Weekday or an Exception day.
   *
   * @return bool
   *   TRUE if the item is Exception day, FALSE otherwise.
   */
  public function isExceptionDay() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = parent::getValue();

    if (!$value) {
      $this->applyDefaultValue();
    }

    $value = $this->formatValue($value);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to no default value.
    $value = [
      'day' => '',
      'starthours' => NULL,
      'endhours' => NULL,
      'comment' => '',
    ];
    $this->setValue($value, $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->isValueEmpty($this->getValue());
  }

  /**
   * Determines whether the data structure is empty.
   *
   * @param array $value
   *   The value of a time slot; day, start, end, comment.
   *   Example from HTML5 input, without comments enabled.
   *   @code
   *     array:3 [
   *       "day" => "3"
   *       "starthours" => array:1 [
   *         "time" => "19:30"
   *       ]
   *       "endhours" => array:1 [
   *         "time" => ""
   *       ]
   *     ]
   *   @endcode
   *
   * @return bool
   *   TRUE if the data structure is empty, FALSE otherwise.
   */
  public static function isValueEmpty(array $value) {
    // Note: in Week-widget, day is <> '', in List-widget, day can be '',
    // and in Exception day, day can be ''.
    // Note: test every change with Week/List widget and Select/HTML5 element!
    if (!isset($value['day']) && !isset($value['time'])) {
      return TRUE;
    }

    // Check Exception day.
    if (OfficeHoursDateHelper::isExceptionDay($value)) {
      if (isset($value['day_delta']) && $value['day_delta'] == 0) {
        // @todo Why is day_delta sometimes not set?
        // First slot is never empty if an Exception day is set.
        return FALSE;
      }
    }

    // Allow Empty time field with comment (#2070145).
    // For 'select list ' and 'html5 datetime' hours element.
    if (isset($value['day'])) {
      if (OfficeHoursDatetime::isEmpty($value['starthours'] ?? '')
      && OfficeHoursDatetime::isEmpty($value['endhours'] ?? '')
      && empty($value['comment'] ?? '')
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Determines whether the data structure is empty.
   *
   * @param array $value
   *   The value of a time slot; day, start, end, comment.
   *
   * @return array
   *   The normalised value of a time slot.
   */
  public static function formatValue(array &$value) {
    if (isset($value['day'])) {
      $day = $value['day'];
      if ($day !== '') {
        // When Form is displayed the first time, $day is an integer.
        // When 'Add exception' is pressed, $day is a string "yyyy-mm-dd".
        $day = is_numeric($day) ? $day : strtotime($day);
        // Convert day number to integer to get '0' for Sunday, not 'false'.
        $day = (int) $day;
      }
      // Format to 'Hi' format, with leading zero (0900).
      $starthours = OfficeHoursDateHelper::format($value['starthours'] ?? NULL, 'Hi');
      $endhours = OfficeHoursDateHelper::format($value['endhours'] ?? NULL, 'Hi');

      $value = [
        'day' => $day,
        // 1. Cast the time to integer, to avoid core's error
        // "This value should be of the correct primitive type."
        // This is needed for e.g., '0000' and '0030'.
        'starthours' => $starthours ? (int) $starthours : NULL,
        'endhours' => $endhours ? (int) $endhours : NULL,
        // Set default value of comment to empty string.
        'comment' => $value['comment'] ?? '',
      ] + $value;
    }
    else {
      // Set default values for new, empty widget.
      $value = [
        'day' => '',
        'starthours' => NULL,
        'endhours' => NULL,
        'comment' => '',
      ];
    }

    return $value;
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
  public static function validateOfficeHoursSettings(array $element, FormStateInterface &$form_state) {
    if (!empty($element['limit_end']['#value']) &&
      $element['limit_end']['#value'] < $element['limit_start']['#value']) {
      $form_state->setError($element['limit_start'], t('%start is later then %end.', [
        '%start' => $element['limit_start']['#title'],
        '%end' => $element['limit_end']['#title'],
      ]));
    }
  }

}
