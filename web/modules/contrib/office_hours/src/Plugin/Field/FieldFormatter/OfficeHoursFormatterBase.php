<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursCacheHelper;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract plugin implementation of the formatter.
 */
abstract class OfficeHoursFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = [
      'day_format' => 'long',
      'time_format' => 'G',
      'compress' => FALSE,
      'grouped' => FALSE,
      'show_closed' => 'all',
      'closed_format' => 'Closed',
      'all_day_format' => 'All day open',
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
        'all_day_format' => 'All day open',
      ],
      'schema' => [
        'enabled' => FALSE,
      ],
      'timezone_field' => '',
      'office_hours_first_day' => '',
    ];
    return $default_settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function mergeDefaults() {
    // Override parent, since that does not support sub-arrays.
    if (isset($this->settings['exceptions'])) {
      if (!is_array($this->settings['exceptions'])) {
        $this->settings['exceptions'] = [];
      }
      $this->settings['exceptions'] += static::defaultSettings()['exceptions'];
    }
    if (isset($this->settings['schema'])) {
      if (!is_array($this->settings['schema'])) {
        $this->settings['schema'] = [];
      }
      $this->settings['schema'] += static::defaultSettings()['schema'];
    }
    parent::mergeDefaults();
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
      '#title' => $this->t('Weekday notation'),
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
        'G' => $this->t('24 hour time') . ' (9:00)',
        'H' => $this->t('24 hour time') . ' (09:00)',
        'g' => $this->t('12 hour time') . ' (9:00 am)',
        'h' => $this->t('12 hour time') . ' (09:00 am)',
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
      '#title' => $this->t('Empty day notation'),
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
    $element['all_day_format'] = [
      '#title' => $this->t('All day notation'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['all_day_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format for all-day-open days.
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
      '#title' => $this->t('Exception days'),
      '#type' => 'details',
      '#open' => FALSE,
      '#description' => $this->t("Note: Exception days can only be entered
        using the '(week) with exceptions' widget."),
    ];
    // Get the exception day formats.
    $formats = $this->entityTypeManager->getStorage('date_format')->loadMultiple();
    // @todo Set date format options using OptionsProviderInterface.
    $options = [];
    foreach ($formats as $format) {
      $options[$format->id()] = $format->get('label');
    }
    $element['exceptions']['restrict_exceptions_to_num_days'] = [
      '#title' => $this->t('Restrict exceptions display to x days in future'),
      '#type' => 'number',
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
      '#title' => $this->t('Title for exceptions section'),
      '#type' => 'textfield',
      '#default_value' => $settings['exceptions']['title'],
      '#description' => $this->t('Leave empty to display no title between weekdays and exception days.'),
      '#required' => FALSE,
    ];
    $element['exceptions']['all_day_format'] = [
      '#title' => $this->t('All day notation for exceptions'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['exceptions']['all_day_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format for all-day-open days.
        String can be translated when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]
      ),
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

    $label = OfficeHoursItem::formatLabel($settings, ['day' => strtotime('today midnight')]);
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
   * @param string $langcode
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
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings, $this->entityTypeManager);

    $new_element = $formatter->viewElements($items, $langcode);
    $elements[] = $new_element[0];
    unset($elements['#cache']);
    return $elements;
  }

  /**
   * Add a 'status' formatter before or after the hours, if necessary.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param string $langcode
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
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings, $this->entityTypeManager);

    $new_element = $formatter->viewElements($items, $langcode);
    unset($new_element['#cache']);

    switch ($new_element[0]['#position']) {
      case 'before':
        array_unshift($elements, $new_element[0]);
        break;

      case'after':
        array_push($elements, $new_element[0]);
        break;

      default:
        break;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    if ($key !== 'display_status') {
      return parent::getSetting($key);
    }

    // Determine if this entity display must be formatted.
    // Return TRUE if render caching must be active.
    // This is the case when:
    // - a Status formatter (open/closed) is used.
    // - only the currently open day is displayed.
    // Note: Also, on the entity itself, it must be checked whether
    // Exception days are used. If so, then caching is also needed.
    if ($this->settings['current_status']['position'] !== '') {
      return TRUE;
    }
    switch ($this->settings['show_closed']) {
      case 'all':
      case 'open':
      case 'none':
        // These caches never expire, since they are always correct.
        return FALSE;

      case 'current':
      case 'next':
      default:
        return TRUE;
    }
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
    // $third_party_settings = $this->getThirdPartySettings();
    //
    // Take the 'open/closed' indicator, if set, since it is the lowest.
    // Overwrite field settings for 'next' formatter'.
    // Only in these cases, we need the formatted office hours.
    // @todo Use $items, not $office_hours in cacheHelper->getCacheMaxAge().
    $cache_needed = (bool) $this->getSetting('display_status');
    if ($cache_needed || $items->hasExceptionDays()) {
      if ($settings['current_status']['position'] !== '') {
        $settings['show_closed'] = 'next';
      }
      $field_settings = $items->getFieldDefinition()->getSettings();
      $office_hours = $items->getRows($settings, $field_settings, []);

      $cache_helper = new OfficeHoursCacheHelper($settings, $items, $office_hours);

      $max_age = $cache_helper->getCacheMaxAge();
      if ($max_age !== Cache::PERMANENT) {
        // @see https://www.drupal.org/docs/drupal-apis/cache-api
        $elements['#cache'] = [
          'max-age' => $max_age,
          'tags' => $cache_helper->getCacheTags(),
          // 'contexts' => $cache_helper->getCacheContexts(),
        ];
      }
    }
  }

}
