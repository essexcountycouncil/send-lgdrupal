<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\field\Entity\FieldConfig;
use Drupal\office_hours\Event\OfficeHoursEvents;
use Drupal\office_hours\Event\OfficeHoursUpdateEvent;
use Drupal\office_hours\OfficeHoursFormatterTrait;
use Drupal\office_hours\OfficeHoursSeason;

/**
 * Represents an Office hours field.
 */
class OfficeHoursItemList extends FieldItemList implements OfficeHoursItemListInterface {

  use OfficeHoursFormatterTrait {
    getRows as getFieldRows;
  }

  /**
   * Helper for creating a list item object of several types.
   *
   * {@inheritdoc}
   */
  protected function createItem($offset = 0, $value = NULL) {
    // @todo Move static variables to class plugin.
    static $pluginManager = NULL;
    $pluginManager = $pluginManager ?? \Drupal::service('plugin.manager.field.field_type');

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $value = $value ?? [];
    $day = $value['day'] ?? NULL;
    if ($day === NULL) {
      // Empty Item from List Widget (or added item via AddMore button?).
      $item = parent::createItem($offset, $value);
    }
    elseif (OfficeHoursItem::isExceptionDay($value)) {
      // Use quasi Factory pattern to create Exception day item.
      $field_type = 'office_hours_exceptions';
      $field_definition = $this->getFieldDefinition($field_type);
      $configuration = [
        'data_definition' => $field_definition->getItemDefinition(),
        'name' => $this->getName(),
        'parent' => $this,
      ];
      $item = $this->typedDataManager->createInstance("field_item:$field_type", $configuration);
      $item->setValue($value);
    }
    elseif (OfficeHoursItem::isSeasonDay($value)) {
      // Add (seasonal) Weekday Item.
      // Copied from FieldTypePluginManager->createInstance().
      $field_type = 'office_hours_season';
      $field_definition = $this->getFieldDefinition($field_type);
      $configuration = [
        'data_definition' => $field_definition->getItemDefinition(),
        'name' => $this->getName(),
        'parent' => $this,
      ];
      $item = $this->typedDataManager->createInstance("field_item:$field_type", $configuration);
      $item->setValue($value);
    }
    else {
      // Add Weekday Item.
      $item = parent::createItem($offset, $value);
    }

    // Pass item to parent, where it appears amongst Weekdays.
    return $item;
  }

  /**
   * {@inheritdoc}
   *
   * Make user all (exception) days are in correct sort order,
   * independent of database order, so formatter is correct.
   * (Widget or other sources may store exceptions day in other sort order).
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
    // Sort the database values by day number.
    $this->sort();

    // Allow other modules to alter $values.
    if (FALSE) {
      $values = $this->getValue();
      // @todo Disabled until #3063782 is resolved.
      $this->dispatchUpdateEvent(OfficeHoursEvents::OFFICE_HOURS_UPDATE, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sort() {
    // Sort the transitions on state weight.
    uasort($this->list, [
      'Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemBase',
      'sort',
    ]);
  }

  /**
   * Dispatches an event.
   *
   * @param string $event_name
   *   The event to trigger.
   * @param array|null $value
   *   An array of values of the field items, or NULL to unset the field.
   *   Can be changed by EventSubscribers.
   *
   * @return \Drupal\sms\Event\SmsMessageEvent
   *   The dispatched event.
   */
  protected function dispatchUpdateEvent($event_name, &$value) {
    // Allow other modules to alter $values.
    $event_dispatcher = \Drupal::service('event_dispatcher');
    /** @var \Drupal\office_hours\Event\OfficeHoursUpdateEvent $event */
    $event = new OfficeHoursUpdateEvent($value);
    $event = $event_dispatcher->dispatch($event);
    $value = $event->getValues();
    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, int $time = 0) {
    return $this->getFieldRows($this->getValue(), $settings, $field_settings, $third_party_settings, $time);
  }

  /**
   * {@inheritdoc}
   *
   * Create a custom field definition for office_hours_* items.
   *
   * Ideally, we just use the basic 'office_hours' field definition.
   * However, this causes either:
   * 1- to display the 'technical' widgets (exception, season) in Field UI,
   *   (with annotation: field_types = {"office_hours"}), or
   * 2- to have the widget refused by WidgetPluginManager~getInstance().
   *   (with annotation: no_ui = TRUE),
   *   FieldType has annotation 'no_ui', FieldWidget and FieldFormatter haven't.
   * So, the Exceptions and Season widgets are now declared for their
   * specific type.
   *
   * @param string $field_type
   *   The field type, 'office_hours' by default.
   *   If set otherwise a new FieldDefinition is returned.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition. BaseField, not ConfigField,
   *   because easier to construct.
   */
  public function getFieldDefinition($field_type = '') {
    static $field_definitions = [];
    switch ($field_type) {
      case '':
      case 'office_hours':
        return parent::getFieldDefinition();

      case 'office_hours_exceptions':
      case 'office_hours_season':
      default:
        $field_definitions[$field_type] = $field_definitions[$field_type]
          ?? FieldConfig::create([
            'entity_type' => $this->getEntity()->getEntityTypeId(),
            'bundle' => $this->getEntity()->bundle(),
            'field_name' => $this->getName(),
            'field_type' => $field_type,
          ]);
        /*
        ?? BaseFieldDefinition::create($field_type)
        ->setName($this->fieldDefinition->getName())
        ->setSettings($this->fieldDefinition->getSettings());
         */
    }
    return $field_definitions[$field_type];

  }

  /**
   * Create an array of seasons. (Do not collect regular or exception days.)
   *
   * @param bool $add_weekdays_as_season
   *   True, if the weekdays must be added as season with ID = 0.
   * @param bool $add_new_season
   *   True, when a default, empty, season must be added.
   *
   * @return \Drupal\office_hours\OfficeHoursSeason[]
   *   A keyed array of seasons. Key = Season ID.
   */
  public function getSeasons($add_weekdays_as_season = FALSE, $add_new_season = FALSE) {
    $seasons = [];
    $season_id = 0;

    if ($add_weekdays_as_season) {
      $seasons[$season_id] = new OfficeHoursSeason($season_id);
    }

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    foreach ($this->list as $item) {
      if ($item->isSeasonHeader()) {
        $season_id = $item->getSeasonId();
        $seasons[$season_id] = new OfficeHoursSeason($item);
      }
    }
    if ($add_new_season) {
      // Add 'New season', until we have a proper 'Add season' button.
      $season_id += OfficeHoursSeason::SEASON_ID_FACTOR;
      $seasons[$season_id] = new OfficeHoursSeason($season_id);
    }

    return $seasons;
  }

}
