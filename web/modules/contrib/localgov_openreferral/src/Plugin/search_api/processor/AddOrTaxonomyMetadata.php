<?php

namespace Drupal\localgov_openreferral\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\localgov_openreferral\MappingInformation;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds Open Referral required Taxonomy information.
 *
 * @SearchApiProcessor(
 *   id = "localgov_openreferral_taxonomy",
 *   label = @Translation("Open Referral Taxonomy MetaData"),
 *   description = @Translation("Adds the terms and facet metadata."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddOrTaxonomyMetadata extends ProcessorPluginBase {

  /**
   * The mapping helper.
   *
   * @var \Drupal\localgov_openreferral\MappingInformation|null
   */
  protected $mappingInformation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setMappingInformation($container->get('localgov_openreferral.mapping_information'));

    return $processor;
  }

  /**
   * Retrieves the mapping helper.
   *
   * @return \Drupal\localgov_openreferral\MappingInformation
   *   The mapping information helper.
   */
  public function getMappingInformation() {
    return $this->mappingInformation ?: \Drupal::service('localgov_openreferral.mapping_information');
  }

  /**
   * Sets the mapping helper.
   *
   * @param \Drupal\localgov_openreferral\MappingInformation $mapping_information
   *   The new mapping information helper.
   *
   * @return $this
   */
  public function setMappingInformation(MappingInformation $mapping_information) {
    $this->mappingInformation = $mapping_information;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Open Referral: Vocabulary'),
        'description' => $this->t('The Open Referral IDs for vocabularies of terms.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['localgov_openreferral_vocabulary'] = new ProcessorProperty($definition);

      $definition = [
        'label' => $this->t('Open Referral: Taxonomy'),
        'description' => $this->t('The Open Referal IDs of terms.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['localgov_openreferral_taxonomy'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();
    if (!($entity instanceof EntityInterface)) {
      return;
    }
    $property_mapping = $this->getMappingInformation()->getPropertyMapping($entity->getEntityTypeId(), $entity->bundle(), '__root');
    if (empty($property_mapping)) {
      return;
    }

    // Item is an entity we have a mapping for.
    $taxonomy_properties = array_filter($property_mapping, function ($map) {
      return in_array($map['public_name'], [
        'service_taxonomys',
        'link_taxonomy',
      ]);
    });

    $vocabularies = [];
    $taxonomies = [];
    foreach (array_column($taxonomy_properties, 'field_name') as $field_name) {
      // @todo incorrect configuration: Log if ≠ EntityReferenceFieldItemList
      //   or/and make sure it's not possible by validating elsewhere?
      foreach ($entity->$field_name->referencedEntities() as $term) {
        $term_map = $this->getMappingInformation()->getPropertyMapping($term->getEntityTypeId(), $term->bundle(), '__root');
        if ($term_map) {
          $vocabularies[] = $this->getMappingInformation()->getPublicDataType($term->getEntityTypeId(), $term->getEntityTypeId(), $term->bundle()) ?? $term->bundle();
          $term_lookup = array_column($term_map, 'field_name', 'public_name');
          $id_field = $term->get($term_lookup['id'])->first();
          $taxonomies[] = $id_field->get($id_field->mainPropertyName())->getValue();
        }
      }
    }

    if (!empty($vocabularies)) {
      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'localgov_openreferral_vocabulary');
      foreach ($fields as $field) {
        foreach ($vocabularies as $vocab) {
          $field->addValue($vocab);
        }
      }
    }

    if (!empty($taxonomies)) {
      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'localgov_openreferral_taxonomy');
      foreach ($fields as $field) {
        foreach ($taxonomies as $term) {
          $field->addValue($term);
        }
      }
    }
  }

}
