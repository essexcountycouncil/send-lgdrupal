<?php

namespace Drupal\feeds;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides a field definition wrapped over a field definition.
 */
class FieldTargetDefinition extends TargetDefinition {

  /**
   * The target plugin id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The wrapped field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * Creates a target definition form a field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return \Drupal\feeds\FieldTargetDefinition
   *   A new target definition.
   */
  public static function createFromFieldDefinition(FieldDefinitionInterface $field_definition) {
    return static::create()
      ->setFieldDefinition($field_definition);
  }

  /**
   * Sets the field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return $this
   */
  protected function setFieldDefinition(FieldDefinitionInterface $field_definition) {
    $this->fieldDefinition = $field_definition;
    return $this;
  }

  /**
   * Sets the plugin id.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return $this
   *   An instance of itself.
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
    return $this;
  }

  /**
   * Returns the field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The wrapped field definition.
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->fieldDefinition->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->fieldDefinition->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyLabel($property) {
    if (!empty($this->properties[$property]['label'])) {
      return $this->properties[$property]['label'];
    }

    $property_definition = $this->fieldDefinition->getItemDefinition()
      ->getPropertyDefinition($property);
    return $property_definition ? $property_definition->getLabel() :
      parent::getPropertyLabel($property);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDescription($property) {
    if (!empty($this->properties[$property]['description'])) {
      return $this->properties[$property]['description'];
    }

    $property_definition = $this->fieldDefinition->getItemDefinition()
      ->getPropertyDefinition($property);
    return $property_definition ? $property_definition->getDescription() :
      parent::getPropertyDescription($property);
  }

}
