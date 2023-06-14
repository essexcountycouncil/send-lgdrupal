<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Implements ItemListInterface for OfficeHours.
 *
 * @package Drupal\office_hours
 */
interface OfficeHoursItemListInterface extends FieldItemListInterface {

  /**
   * Returns the items of a field.
   *
   * @param array $settings
   *   The formatter settings.
   * @param array $field_settings
   *   The field settings.
   * @param array $third_party_settings
   *   The formatter's third party settings.
   * @param int $time
   *   A time.
   *
   * @return array
   *   The formatted list of slots.
   *
   * @usage The function is not used anymore in module, but is used in local
   * installations theming in twig, skipping the Drupal field UI/formatters.
   * Since twig filters are static methods, a trait is not really an option.
   * Some installations are also subclassing this class.
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, $time = NULL);

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
  public function getFieldDefinition($field_type = '');

  /**
   * Get the current slot and the next day from the Office hours.
   *
   * - Variable $this->nextDay is set to day number.
   * - Attribute 'current' is set on the active slot.
   * - Variable $this->currentSlot is set to slot data.
   * - Variable $this->currentSlot is returned.
   *
   * @param mixed $time
   *   The desired timestamp.
   *
   * @return null|\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem
   *   The current slot data, if any.
   */
  public function getCurrent($time = NULL);

  /**
   * Determines if the Entity has Exception days.
   *
   * @return bool
   *   Indicator whether the entity has Exception days.
   */
  public function hasExceptionDays();

  /**
   * Determines if the Entity is Open or Closed.
   *
   * @param int $time
   *   A time.
   *
   * @return bool
   *   Indicator whether the entity is Open or Closed at the given time.
   */
  public function isOpen($time = NULL);

}
