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
   * Since twig filters are static methods, using a trait isnt really an option.
   * Some installations are also subclassing this class.
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, $time = NULL);

  /**
   * Returns the formatter caching time of a field.
   *
   * @param array $settings
   *   The formatter settings.
   * @param array $field_settings
   *   The field settings.
   * @param array $third_party_settings
   *   The formatter's third party settings.
   *
   * @return int
   *   The time that a render element (formatter) can be cached.
   *
   * @see https://www.drupal.org/docs/8/api/cache-api/cache-max-age
   */
  public function getCacheTime(array $settings, array $field_settings, array $third_party_settings);

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
