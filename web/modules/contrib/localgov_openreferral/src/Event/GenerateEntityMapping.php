<?php

namespace Drupal\localgov_openreferral\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Generate mapping from Drupal entity type to Open Referral class.
 */
class GenerateEntityMapping extends Event {

  public const GENERATE = 'localgov_openreferral.generate_mapping';

  /**
   * The suggested mapping.
   *
   * Array in the format of PropertyMapping.
   *
   * @var array
   */
  public $mapping = [];

  /**
   * The entity type id to suggest mapping for.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle to suggest mapping for.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The Open Referral class name to map to.
   *
   * @var string
   */
  protected $publicType;

  /**
   * Event constructor.
   *
   * @param string $entity_type_id
   *   The entity type to suggest mapping for.
   * @param string $bundle
   *   The bundle to suggest mapping for.
   * @param string $public_type
   *   The Open Referral class name to make mapping suggestion.
   */
  public function __construct(string $entity_type_id, string $bundle, string $public_type) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
    $this->publicType = $public_type;
  }

  /**
   * Get entity type id.
   *
   * @return string
   *   The entity type id for mapping.
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * Get bundle.
   *
   * @return string
   *   The bundle.
   */
  public function getBundle(): string {
    return $this->bundle;
  }

  /**
   * Get Open Referral class name.
   *
   * @return string
   *   The class name.
   */
  public function getPublicType(): string {
    return $this->publicType;
  }

  /**
   * Helper: Add mapping if field_name doesn't yet have a mapping.
   *
   * @param array $suggestions
   *   Array of mappings.
   */
  public function addSuggestions(array $suggestions): void {
    $keyed_mapping = count($this->mapping) ? array_combine(
      array_column($this->mapping, 'field_name'),
      $this->mapping
    ) : [];
    $keyed_suggestions = count($suggestions) ? array_combine(
      array_column($suggestions, 'field_name'),
      $suggestions
    ) : [];
    $keyed_mapping += $keyed_suggestions;
    $this->mapping = array_values($keyed_mapping);
  }

  /**
   * Helper: Add mapping if field_name, and public_name, don't yet have a map.
   *
   * @param array $suggestions
   *   Array of mappings.
   */
  public function addSuggestionsSingle(array $suggestions): void {
    $existing_field_names = array_column($this->mapping, 'field_name');
    $existing_public_names = array_column($this->mapping, 'public_name');
    foreach ($suggestions as $suggestion) {
      if (
        (!in_array($suggestion['field_name'], $existing_field_names))
        && (!in_array($suggestion['public_name'], $existing_public_names))
      ) {
        $this->mapping[] = $suggestion;
      }
    }
  }

}
