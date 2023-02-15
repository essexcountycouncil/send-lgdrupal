<?php

namespace Drupal\entity_reference_facet_link\Plugin\Field\FieldFormatter;


use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\facets\Result\Result;
use Drupal\facets\UrlProcessor\UrlProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntityReferenceFacetFormatterBase extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * A facet entity.
   *
   * @var \Drupal\facets\FacetInterface
   */
  protected $facet;

  /**
   * The facet entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * A URL processor plugin manager.
   *
   * @var \Drupal\facets\UrlProcessor\UrlProcessorPluginManager
   */
  protected $urlProcessorPluginManager;

  /**
   * Constructs a EntityReferenceFacetLink object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $facet_storage
   *   An entity type manager.
   * @param \Drupal\facets\UrlProcessor\UrlProcessorPluginManager $url_processor_plugin_manager
   *   A URL processor plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $facet_storage, UrlProcessorPluginManager $url_processor_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->facetStorage = $facet_storage;
    $this->urlProcessorPluginManager = $url_processor_plugin_manager;
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
      $container->get('entity_type.manager')->getStorage('facets_facet'),
      $container->get('plugin.manager.facets.url_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($facet = $this->getFacet()) {
      $dependencies[$facet->getConfigDependencyKey()][] = $facet->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['facet' => ''] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\facets\FacetInterface[] $facets */
    $facets = $this->facetStorage->loadMultiple();
    $options = [];
    foreach ($facets as $facet) {
      $definition = $facet->getDataDefinition($facet->getFieldIdentifier());
      // Ensure that we are only dealing with facets associated with fields.
      if ($definition instanceof FieldItemDataDefinition) {
        $facet_field_name = $definition->getFieldDefinition()->getName();

        // Add a facet to the options only if that facet is faceting this field.
        if ($facet_field_name == $this->fieldDefinition->getName()) {
          $options[$facet->id()] = $facet->label();
        }
      }
    }

    $elements['facet'] = [
      '#title' => $this->t('Select the facet to which the labels should be linked.'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('facet'),
      '#options' => $options,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($facet = $this->getFacet()) {
      $summary[] = $this->t('Selected facet: @facet', ['@facet' => $facet->label()]);
    }
    else {
      $summary[] = $this->t('No facet selected');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $facet = $this->getFacet();
    if (empty($facet)) {
      return [];
    }

    // Instead of trying to guess how the facet URLs should be formatted, let
    // the facet's own URL processor do the work of building them.  Then the
    // URLs will be formatted correctly no matter what processor is being used,
    // for instance Facets Pretty Paths.
    $url_processor_id = $facet->getFacetSourceConfig()->getUrlProcessorName();
    $configuration = ['facet' => $facet];
    /** @var \Drupal\facets\UrlProcessor\UrlProcessorInterface $url_processor */
    $url_processor = $this->urlProcessorPluginManager->createInstance($url_processor_id, $configuration);

    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Create a fake Result object from the field item so that we can pass
      // it to the URL processor.
      $result = new Result($facet, $entity->id(), $entity->label(), 0);
      $result = $url_processor->buildUrls($facet, [$result])[0];

      // Invalidate the cache when the referenced entity or the facet source
      // config changes.  The source display config, for instance a view, should
      // be added here too, but there really isn't any way to access that config
      // entity through the API.
      $cache_tags = Cache::mergeTags($entity->getCacheTags(), $facet->getFacetSourceConfig()->getCacheTags());

      $elements[$delta] = $this->buildElement($result->getUrl(), $entity) + [
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }
    return $elements;
  }

  /**
   * Builds a single element's render array.
   *
   * @param \Drupal\Core\Url $url
   *   The processed facet URL.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being displayed.
   *
   * @return array
   *   A render array.
   */
  abstract protected function buildElement(Url $url, EntityInterface $entity);

  /**
   * Gets the configured facet entity.
   *
   * @return \Drupal\facets\FacetInterface|null
   *   The configured facet or null if not set.
   */
  protected function getFacet() {
    if (!isset($this->facet)) {
      $this->facet = $this->facetStorage->load($this->getSetting('facet'));
    }
    return $this->facet;
  }

}
