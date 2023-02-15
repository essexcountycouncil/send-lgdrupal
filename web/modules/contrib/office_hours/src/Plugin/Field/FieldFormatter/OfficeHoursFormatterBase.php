<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Abstract plugin implementation of the formatter.
 */
abstract class OfficeHoursFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'day_format' => 'long',
      'time_format' => 'G',
      'compress' => FALSE,
      'grouped' => FALSE,
      'show_closed' => 'all',
      'closed_format' => 'Closed',
      'separator' => [
        'days' => '<br />',
        'grouped_days' => ' - ',
        'day_hours' => ': ',
        'hours_hours' => '-',
        'more_hours' => ', ',
      ],
      'current_status' => [
        'position' => '', // Hidden.
        'open_text' => 'Currently open!',
        'closed_text' => 'Currently closed',
      ],
      'exceptions' => [
        'restrict_exceptions_to_num_days' => 7,
        'date_format' => 'long',
        'title' => 'Exception hours',
      ],
      'schema' => [
        'enabled' => FALSE,
      ],
      'timezone_field' => '',
      'office_hours_first_day' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $settings = $this->getSettings();
    $day_names = OfficeHoursDateHelper::weekDays(FALSE);
    $day_names[''] = $this->t("- system's Regional settings -");

    /*
    // Find timezone fields, to be used in 'Current status'-option.
    $fields = field_info_instances( (isset($form['#entity_type'])
      ? $form['#entity_type']
      : NULL), (isset($form['#bundle']) ? $form['#bundle'] : NULL));
    $timezone_fields = [];
    foreach ($fields as $field_name => $timezone_instance) {
      if ($field_name == $field['field_name']) {
        continue;
      }
      $timezone_field = field_read_field($field_name);

      if (in_array($timezone_field['type'], ['tzfield'])) {
        $timezone_fields[$timezone_instance['field_name']]
        = $timezone_instance['label']
        . ' ('
        . $timezone_instance['field_name'] . ')';
      }
    }
    if ($timezone_fields) {
      $timezone_fields = ['' => '<None>'] + $timezone_fields;
    }
     */

    $element['show_closed'] = [
      '#title' => $this->t('Number of days to show'),
      '#type' => 'select',
      '#options' => [
        'all' => $this->t('Show all days'),
        'open' => $this->t('Show only open days'),
        'next' => $this->t('Show next open day'),
        'none' => $this->t('Hide all days'),
        'current' => $this->t('Show only current day'),
      ],
      '#default_value' => $settings['show_closed'],
      '#description' => $this->t('The days to show in the formatter. Useful in combination with the Current Status block.'),
    ];
    // First day of week, copied from system.variable.inc.
    $element['office_hours_first_day'] = [
      '#title' => $this->t('First day of week'),
      '#type' => 'select',
      '#options' => $day_names,
      '#default_value' => $this->getSetting('office_hours_first_day'),
    ];
    $element['day_format'] = [
      '#title' => $this->t('Day notation'),
      '#type' => 'select',
      '#options' => [
        'long' => $this->t('long'),
        'short' => $this->t('3-letter weekday abbreviation'),
        'two_letter' => $this->t('2-letter weekday abbreviation'),
        'number' => $this->t('number'),
        'none' => $this->t('none'),
      ],
      '#default_value' => $settings['day_format'],
    ];
    // @todo D8 Align with DateTimeDatelistWidget.
    $element['time_format'] = [
      '#title' => $this->t('Time notation'),
      '#type' => 'select',
      '#options' => [
        'G' => $this->t('24 hour time') . ' (9:00)', // D7: key = 0.
        'H' => $this->t('24 hour time') . ' (09:00)', // D7: key = 2.
        'g' => $this->t('12 hour time') . ' (9:00 am)', // D7: key = 1.
        'h' => $this->t('12 hour time') . ' (09:00 am)', // D7: key = 1.
      ],
      '#default_value' => $settings['time_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format of the clock in the formatter.'),
    ];
    $element['compress'] = [
      '#title' => $this->t('Compress all hours of a day into one set'),
      '#type' => 'checkbox',
      '#default_value' => $settings['compress'],
      '#description' => $this->t('Even if more hours is allowed, you might want to show a compressed form. E.g., 7:00-12:00, 13:30-19:00 becomes 7:00-19:00.'),
      '#required' => FALSE,
    ];
    $element['grouped'] = [
      '#title' => $this->t('Group consecutive days with same hours into one set'),
      '#type' => 'checkbox',
      '#default_value' => $settings['grouped'],
      '#description' => $this->t('E.g., Mon: 7:00-19:00; Tue: 7:00-19:00 becomes Mon-Tue: 7:00-19:00.'),
      '#required' => FALSE,
    ];
    $element['closed_format'] = [
      '#title' => $this->t('Empty days notation'),
      '#type' => 'textfield',
      '#size' => 30,
      '#default_value' => $settings['closed_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format of empty (closed) days.
        String can be translated when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]
      ),
    ];

    // Taken from views_plugin_row_fields.inc.
    $element['separator'] = [
      '#title' => $this->t('Separators'),
      '#type' => 'details',
      '#open' => FALSE,
    ];
    $element['separator']['days'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['days'],
      '#description' => $this->t('This separator will be placed between the days. Use &#39&ltbr&gt&#39 to show each day on a new line.'),
    ];
    $element['separator']['grouped_days'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['grouped_days'],
      '#description' => $this->t('This separator will be placed between the labels of grouped days.'),
    ];
    $element['separator']['day_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['day_hours'],
      '#description' => $this->t('This separator will be placed between the day and the hours.'),
    ];
    $element['separator']['hours_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['hours_hours'],
      '#description' => $this->t('This separator will be placed between the hours of a day.'),
    ];
    $element['separator']['more_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['more_hours'],
      '#description' => $this->t('This separator will be placed between the hours and more_hours of a day.'),
    ];

    // Show a 'Current status' option.
    $element['current_status'] = [
      '#title' => $this->t('Current status'),
      '#type' => 'details',
      '#open' => FALSE,
      '#description' => $this->t('Below strings can be translated when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]),
    ];
    $element['current_status']['position'] = [
      '#title' => $this->t('Current status position'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('Hidden'),
        'before' => $this->t('Before hours'),
        'after' => $this->t('After hours'),
      ],
      '#default_value' => $settings['current_status']['position'],
      '#description' => $this->t('Where should the current status be located?'),
    ];
    $element['current_status']['open_text'] = [
      '#title' => $this->t('Status strings'),
      '#type' => 'textfield',
      '#size' => 40,
      '#default_value' => $settings['current_status']['open_text'],
      '#description' => $this->t('Format of the message displayed when currently open.'),
    ];
    $element['current_status']['closed_text'] = [
      '#type' => 'textfield',
      '#size' => 40,
      '#default_value' => $settings['current_status']['closed_text'],
      '#description' => $this->t('Format of message displayed when currently closed.'),
    ];

    $element['exceptions'] = [
      '#title' => $this->t('Exception day handling'),
      '#type' => 'details',
      '#open' => FALSE,
      '#description' => $this->t("Note: Exception days can only be entered
        using the '(week) with exceptions' widget."),
    ];
    // Get the exception date formats.
    // @todo Use $container->get('entity_type.manager').
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $formats = $entity_type_manager->getStorage('date_format')->loadMultiple();
    // Set select list options for the date format. @todo OptionsProviderInterface.
    $options = [];
    foreach ($formats as $format) {
      $options[$format->id()] = $format->get('label');
    }
    $element['exceptions']['restrict_exceptions_to_num_days'] = [
      '#title' => $this->t('Restrict exceptions display to x days in future'),
      '#type' => 'number',
      // '#default_value' => $settings['exceptions']['restrict_exceptions_to_num_days'],
      '#default_value' => $settings['exceptions']['restrict_exceptions_to_num_days'],
      '#min' => 0,
      '#max' => 99,
      '#step' => 1,
      '#description' => $this->t("To enable Exception days, set a non-zero number (to be used in the formatter) and select an 'Exceptions' widget."),
      '#required' => TRUE,
    ];
    // @todo Add link to admin/config/regional/date-time.
    $element['exceptions']['date_format'] = [
      '#title' => $this->t('Date format for exception day'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $settings['exceptions']['date_format'],
      '#description' => $this->t("Maintain additional date formats <a href=':url'>here</a>.", [
        ':url' => Url::fromRoute('entity.date_format.collection')->toString(),
      ]),
      '#required' => TRUE,
    ];
    // @todo Move to field settings, since used in both Formatter and Widget.
    $element['exceptions']['title'] = [
      '#title' => $this->t('Title for exceptions'),
      '#type' => 'textfield',
      '#default_value' => $settings['exceptions']['title'],
      '#description' => $this->t('Leave empty to display no title between weekdays and exception days.'),
      '#required' => FALSE,
    ];

    $element['schema'] = [
      '#title' => $this->t('Schema.org openingHours support'),
      '#type' => 'details',
      '#open' => FALSE,
    ];
    $element['schema']['enabled'] = [
      '#title' => $this->t('Enable Schema.org openingHours support'),
      '#type' => 'checkbox',
      '#default_value' => $settings['schema']['enabled'],
      '#description' => $this->t('Enable meta tags with property for Schema.org openingHours.'),
      '#required' => FALSE,
    ];

    /*
    if ($timezone_fields) {
      $element['timezone_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Timezone') . ' ' . $this->t('Field'),
        '#options' => $timezone_fields,
        '#default_value' => $settings['timezone_field'],
        '#description' => $this->t('Should we use another field to set the timezone for these hours?'),
      ];
    }
    else {
      $element['timezone_field'] = [
        '#type' => 'hidden',
        '#title' => $this->t('Timezone') . ' ' . $this->t('Field'),
        '#value' => $settings['timezone_field'],
      ];
    }
     */

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();

    // @todo Return more info, like Date module does.
    $summary[] = $this->t('Display Office hours in different formats.');

    $label = OfficeHoursDateHelper::getLabel($settings['exceptions']['date_format'], ['day' => strtotime('today midnight')]);
    $summary[] = $this->t("Show '@title' until @time days in the future.", [
        '@time' => $settings['exceptions']['restrict_exceptions_to_num_days'],
        '@date' => $settings['exceptions']['date_format'],
        '@title' => $settings['exceptions']['title'] == '' ? $this->t('Exception days') : $this->t($settings['exceptions']['title']),
      ]) . ' ' . $this->t("Example: $label");

    return $summary;
  }

  /**
   * Add an 'openingHours' formatter from https://schema.org/openingHours.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param $langcode
   *   The required language code.
   * @param array $elements
   *   Elements.
   *
   * @return array
   *   A formatter element.
   */
  protected function addSchemaFormatter(OfficeHoursItemListInterface $items, $langcode, array $elements) {
    if (empty($this->settings['schema']['enabled'])) {
      return $elements;
    }

    $formatter = new OfficeHoursFormatterSchema(
      $this->pluginId, $this->pluginDefinition, $this->fieldDefinition,
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings);

    $new_element = $formatter->viewElements($items, $langcode);
    $elements[] = $new_element[0];
    return $elements;
  }

  /**
   * Add a 'status' formatter before or after the hours, if necessary.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param $langcode
   *   The required language code.
   * @param array $elements
   *   Elements.
   *
   * @return array
   *   A formatter element.
   */
  protected function addStatusFormatter(OfficeHoursItemListInterface $items, $langcode, array $elements) {

    if (empty($this->settings['current_status']['position'])) {
      return $elements;
    }

    $formatter = new OfficeHoursFormatterStatus(
      $this->pluginId, $this->pluginDefinition, $this->fieldDefinition,
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings);

    $new_element = $formatter->viewElements($items, $langcode);

    switch ($new_element['#position']) {
      case 'before':
        array_unshift($elements, $new_element);
        break;

      case'after':
        array_push($elements, $new_element);
        break;

      default:
        break;
    }

    return $elements;
  }

  /**
   * Add a ['#cache']['max-age'] attribute to $elements, if necessary.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param array $elements
   *   The list of formatters.
   */
  protected function addCacheMaxAge(OfficeHoursItemListInterface $items, array &$elements) {
    $settings = $this->getSettings();
    $third_party_settings = $this->getThirdPartySettings();

    $max_age = $items->getCacheTime($settings, $this->getFieldSettings(), $third_party_settings);
    if ($max_age !== Cache::PERMANENT) {
      $entity = $items->getEntity();

      $cache_tags = $entity->getEntityTypeId() . ':' . $entity->id();
      $elements['#cache'] = [
        'max-age' => $max_age,
        // @see https://www.drupal.org/docs/drupal-apis/cache-api/cache-tags
        'tags' => [$cache_tags],
      ];
    }
  }

}
