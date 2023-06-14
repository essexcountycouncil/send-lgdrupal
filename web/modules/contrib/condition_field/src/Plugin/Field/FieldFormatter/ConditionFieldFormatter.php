<?php

namespace Drupal\condition_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'condition_field_string' formatter.
 *
 * @FieldFormatter(
 *   id = "condition_field_string",
 *   label = @Translation("Condition field formatter"),
 *   field_types = {
 *     "condition_field"
 *   }
 * )
 */
class ConditionFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ExecutableManagerInterface $manager, ContextRepositoryInterface $context_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->manager = $manager;
    $this->contextRepository = $context_repository;
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
      $container->get('plugin.manager.condition'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The generated output.
   */
  protected function viewValue(FieldItemInterface $item) {
    $summaries = [];
    $conditions = $item->conditions;
    foreach ($conditions as $condition_id => $config) {
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->manager->createInstance($condition_id, $config ?? []);
      $label = $condition->getPluginDefinition()['label'];
      $summary = $condition->summary();
      $summaries[$condition_id] = $this->t(
        '<strong>@label</strong>: @summary',
        ['@label' => $label, '@summary' => $summary]
      );
    }

    return [
      '#theme' => 'item_list',
      '#items' => $summaries,
    ];
  }

}
