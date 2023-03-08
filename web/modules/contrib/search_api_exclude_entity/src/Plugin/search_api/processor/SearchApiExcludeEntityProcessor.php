<?php

namespace Drupal\search_api_exclude_entity\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Excludes entities marked as 'excluded' from being indexes.
 *
 * @SearchApiProcessor(
 *   id = "search_api_exclude_entity_processor",
 *   label = @Translation("Search API Exclude Entity"),
 *   description = @Translation("Exclude entities from being indexed, if they are excluded by 'Search API Exclude' module."),
 *   stages = {
 *     "alter_items" = -50
 *   }
 * )
 */
class SearchApiExcludeEntityProcessor extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|null
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->setEntityFieldManager($container->get('entity_field.manager'));
    return $processor;
  }

  /**
   * Retrieves the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  public function getEntityFieldManager() {
    return $this->entityFieldManager ?: \Drupal::service('entity_field.manager');
  }

  /**
   * Sets the entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The new entity field manager.
   *
   * @return $this
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $fields_config = $this->getConfiguration()['fields'];

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $datasource_id_expl = explode(':', $datasource_id);
      $entity_type = next($datasource_id_expl);

      $form['fields'][$entity_type] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Fields entity type: %type', ['%type' => $datasource->label()]),
        '#description' => $this->t('Choose the Search API Exclude fields that should be used to exclude entities in this index.'),
        '#default_value' => $fields_config[$entity_type] ?? [],
        '#options' => $this->getFieldOptions($entity_type, $datasource),
        '#multiple' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Remove non selected values.
    if (isset($values['fields']) && is_array($values['fields'])) {
      foreach ($values['fields'] as $entity => $field) {
        $values['fields'][$entity] = array_values(array_filter($field));
      }
    }

    $this->setConfiguration($values);
  }

  /**
   * Find and return entity bundles enabled on the active index.
   *
   * @param string $entity_type
   *   The entity type we are finding bundles for.
   * @param object $datasource
   *   The data source from the active index.
   *
   * @return array
   *   Options array with bundles.
   */
  private function getFieldOptions($entity_type, $datasource) {
    $field_map = $this->getEntityFieldManager()->getFieldMapByFieldType('search_api_exclude_entity');
    $bundles = $datasource->getBundles();

    $options = [];

    if (isset($field_map[$entity_type])) {
      foreach ($field_map[$entity_type] as $field_id => $field) {
        $bundles_filtered = [];
        foreach ($field['bundles'] as $field_bundle) {
          if (isset($bundles[$field_bundle])) {
            $bundles_filtered[] = $field_bundle;
          }
        }

        if (count($bundles_filtered) > 0) {
          $options[$field_id] = $field_id . ' (' . implode(', ', $bundles_filtered) . ')';
        }
      }
    }

    return $options;
  }

  /**
   * Checking if a specific entity bundle has a specific field.
   *
   * @param string $entity_type
   *   Entity type we are using in the field check.
   * @param string $bundle
   *   Bundle we are using in the field check.
   * @param string $field
   *   The field we are checking if it is being used by the bundle.
   *
   * @return bool
   *   TRUE if the entity bundle has the field, FALSE otherwise.
   */
  private function bundleHasField($entity_type, $bundle, $field) {
    static $field_map;

    if (!isset($field_map)) {
      $field_map = $this->getEntityFieldManager()->getFieldMapByFieldType('search_api_exclude_entity');
    }

    return isset($field_map[$entity_type][$field]['bundles'][$bundle]);
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    $config = $this->getConfiguration()['fields'];

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      if (!$object instanceof EntityInterface) {
        continue;
      }
      $entity_type_id = $object->getEntityTypeId();
      $bundle = $object->bundle();

      if (isset($config[$entity_type_id]) && is_array($config[$entity_type_id])) {
        foreach ($config[$entity_type_id] as $field) {

          // We need to be sure that the field actually exists on the bundle
          // before getting the value to avoid InvalidArgumentException
          // exceptions.
          if ($this->bundleHasField($entity_type_id, $bundle, $field)) {
            $value = $object->get($field)->getValue();
            if (isset($value[0]['value']) && $value[0]['value'] !== NULL) {
              $value = $value[0]['value'];
              if ($value) {
                unset($items[$item_id]);
                continue;
              }
            }
          }
        }
      }
    }
  }

}
