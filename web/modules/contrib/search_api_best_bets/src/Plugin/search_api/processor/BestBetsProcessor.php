<?php

namespace Drupal\search_api_best_bets\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api_best_bets\QueryHandler\QueryHandlerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Processor.
 *
 * @SearchApiProcessor(
 *   id = "search_api_best_bets_processor",
 *   label = @Translation("Search API Best Bets"),
 *   description = @Translation("Check if any editorial best bets match the search query and include them in the query to the search backend."),
 *   stages = {
 *     "preprocess_query" = 99,
 *     "postprocess_query" = 10
 *   }
 * )
 */
class BestBetsProcessor extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The best bets handler plugin manager.
   *
   * @var \Drupal\search_api_best_bets\QueryHandler\QueryHandlerPluginManager
   */
  protected $queryHandlerManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Store the best bets items included in the search query.
   *
   * @var array
   */
  protected $bestBetsItems;

  /**
   * {@inheritdoc}
   */
  public function __construct(
                              array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              QueryHandlerPluginManager $query_handler_plugin_manager,
                              ConfigFactoryInterface $config_factory,
                              EntityRepositoryInterface $entity_repository,
                              EntityTypeManagerInterface $entity_type_manager,
                              ModuleHandlerInterface $module_handler,
                              EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queryHandlerManager = $query_handler_plugin_manager;
    $this->configFactory = $config_factory;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.search_api_best_bets.query_handler'),
      $container->get('config.factory'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
      'query_handler' => '',
      'result_elevated_flag' => 'query_handler',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config_fields = $this->getConfiguration()['fields'];
    $config_query_handler = $this->getConfiguration()['query_handler'];
    $config_result_elevated_flag = $this->getConfiguration()['result_elevated_flag'];

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $datasource_id_expl = explode(':', $datasource_id);
      $entity_type = next($datasource_id_expl);

      $form['fields'][$entity_type] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Fields entity type: %type', ['%type' => $datasource->label()]),
        '#description' => $this->t('Choose the Search API Best Bets fields that should be used to elevate / exclude items in the search result.'),
        '#default_value' => $config_fields[$entity_type] ?? [],
        '#options' => $this->getFieldOptions($entity_type, $datasource),
        '#multiple' => TRUE,
      ];
    }

    $form['query_handler'] = [
      '#type' => 'select',
      '#title' => $this->t('Query handler'),
      '#description' => $this->t('Choose the query handler plugin that should be used to elevate / exclude items on query time.'),
      '#default_value' => !empty($config_query_handler) ? $config_query_handler : NULL,
      '#options' => $this->getQueryHandlerOptions(),
    ];

    $form['result_elevated_flag'] = [
      '#type' => 'select',
      '#title' => $this->t('Elevated flag'),
      '#description' => $this->t('Should we get the elevated flag from the query handler plugin or set it locally based on the elevated items send to the search backend?'),
      '#default_value' => !empty($config_result_elevated_flag) ? $config_result_elevated_flag : 'query_handler',
      '#options' => [
        'query_handler' => $this->t('Query handler plugin'),
        'local' => $this->t('Process locally'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Check that a field has been selected.
    $error = TRUE;
    if (isset($values['fields']) && is_array($values['fields'])) {
      foreach ($values['fields'] as $fields) {
        foreach ($fields as $field) {
          if ($field) {
            $error = FALSE;
          }
        }
      }
    }
    if ($error) {
      $form_state->setErrorByName('', $this->t('Choose at least one field to enable the best bets feature.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Remove non-selected values.
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
    $field_map = $this->entityFieldManager->getFieldMapByFieldType('search_api_best_bets');
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
   * Find and return a list of best bets handler plugins.
   *
   * It finds the plugins that support the current backend.
   *
   * @return array
   *   Options with with handler plugins.
   */
  private function getQueryHandlerOptions() {
    $backend = $this->getIndex()->getServerInstance()->get('backend');
    $handlers = $this->queryHandlerManager->getAvailableQueryHandlersByBackend($backend);
    $options = ['' => $this->t('- Select -')];

    foreach ($handlers as $id => $handler) {
      $options[$id] = $handler['label'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $config = $this->getConfiguration();

    if ($plugin_id = $config['query_handler']) {
      $config['fields'];

      // Get the current keys.
      $keys = $query->getOriginalKeys();

      // We only handle the simple keys at the moment.
      // @todo Implement handling of complex keys.
      if (!is_scalar($keys)) {
        return;
      }

      // Removing quotes and white spaces.
      $keys = trim(trim(urldecode($keys), '\"\''));

      // Stop if the key is empty.
      if (empty($keys)) {
        return;
      }

      // Check if we have a valid query handler plugin.
      if (!$this->queryHandlerManager->validatePlugin($plugin_id)) {
        return;
      }

      // Query handler plugin instance.
      $instance = $this->queryHandlerManager->createInstance($plugin_id, []);

      // Check if we have any results that should be elevated.
      if ($elevate = $this->getBestBets($keys)) {
        $this->bestBetsItems['elevate'] = $elevate;
      }

      // Check if we have any results that should be excluded.
      if ($exclude = $this->getBestBets($keys, TRUE)) {
        $this->bestBetsItems['exclude'] = $exclude;
      }

      // Call the plugin for adding the query params.
      if (!empty($this->bestBetsItems)) {
        $instance->alterQuery($this->bestBetsItems, $query);
      }
    }
  }

  /**
   * Check if we have any bets bets matching the search keys.
   *
   * @param string $keys
   *   String with search keys.
   * @param bool $exclude
   *   Exclude = TRUE, Elevate = FALSE.
   *
   * @return array
   *   Array with entities.
   */
  private function getBestBets($keys, $exclude = FALSE) {
    $config = $this->getConfiguration();

    // Ensure that the keys are lowercase.
    $keys = mb_strtolower($keys);

    // Stop if we do not have any fields configured.
    if (!isset($config['fields']) || !is_array($config['fields'])) {
      return [];
    }

    // Check all configured entity types and fields.
    $result = [];
    foreach ($config['fields'] as $entity_type => $fields) {
      foreach ($fields as $field) {
        if ($entity_type && $field) {
          // Find entities with best bets matching the keys.
          $query = $this->entityTypeManager
            ->getStorage($entity_type)
            ->getQuery()
            ->condition($field . '.query_text', $keys)
            ->condition($field . '.exclude', (int) $exclude)
            ->accessCheck()
            ->currentRevision();
          $ids = $query->execute();

          // Loop through all returned ids, check access and prepare result.
          foreach ($ids as $id) {
            $entity_storage = $this->entityTypeManager->getStorage($entity_type);
            $source_entity = $entity_storage->load($id);

            // Get correct translation of the source entity.
            $entity = $this->entityRepository->getTranslationFromContext($source_entity);

            // Check access.
            if ($entity->access('view')) {
              $result[] = $this->generateItemId($entity);
            }
          }
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    $config = $this->getConfiguration();

    if ($plugin_id = $config['query_handler']) {

      // Check if we have a valid query handler plugin.
      if (!$this->queryHandlerManager->validatePlugin($plugin_id)) {
        return;
      }

      // Get the elevated flag from the query handler.
      if ($config['result_elevated_flag'] == 'query_handler') {
        // Query handler plugin instance.
        $instance = $this->queryHandlerManager->createInstance($plugin_id, []);

        // Call the plugin for altering the results.
        $instance->alterResults($results);
      }
      // Set the elevated locally based on elevated items send to
      // search backend.
      else {
        $this->setElevatedFlags($results);
      }
    }
  }

  /**
   * Set the elevated flags for specific result items.
   *
   * @param Drupal\search_api\Query\ResultSetInterface $results
   *   The result items.
   */
  private function setElevatedFlags(ResultSetInterface &$results) {
    if (empty($this->bestBetsItems['elevate'])) {
      return;
    }

    $elevated_items = array_flip($this->bestBetsItems['elevate']);
    $items = $results->getResultItems();

    // Process items and add the extra elevate data.
    // We only want elevated items in the top of the result.
    // Other items are skipped.
    foreach ($items as $item) {
      if ($item->getId() && isset($elevated_items[$item->getId()])) {
        $item->setExtraData('elevated', TRUE);
        $results->addResultItem($item);
      }
      else {
        break;
      }
    }
  }

  /**
   * Generate the Search API Item ID.
   *
   * @todo Get the Search API Item ID from Search API instead of generating
   * @todo it our selves.
   *
   * @param object $entity
   *   The entity object.
   *
   * @return string
   *   The generated Search API Item ID.
   */
  private function generateItemId($entity) {
    $pieces = [
      'entity',
      ':',
      $entity->getEntityTypeId(),
      '/',
      $entity->id(),
      ':',
      $entity->language()->getId(),
    ];

    return implode('', $pieces);
  }

}
