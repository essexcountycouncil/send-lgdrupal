<?php

namespace Drupal\localgov_openreferral\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\localgov_openreferral\MappingInformation;
use Drupal\localgov_openreferral\QueryPagerTrait;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Open Referral API endpoint routes.
 */
class EndpointsController extends ControllerBase implements ContainerInjectionInterface {

  use QueryPagerTrait;

  /**
   * HTTP Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Mapping information service.
   *
   * @var \Drupal\localgov_openreferral\MappingInformation
   */
  protected $mappingInformation;

  /**
   * Controller constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request.
   * @param \Drupal\localgov_openreferral\MappingInformation $mapping_information
   *   Mapping information helper service.
   */
  public function __construct(Request $request, MappingInformation $mapping_information) {
    $this->request = $request;
    $this->mappingInformation = $mapping_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('localgov_openreferral.mapping_information')
    );
  }

  /**
   * Single entity endpoint.
   */
  public function single(ContentEntityInterface $entity) {
    $response = new ResourceResponse($entity, 200);

    $response->addCacheableDependency($entity);

    foreach ($entity as $field_name => $field) {
      assert($field instanceof FieldItemListInterface);
      $field_access = $field->access('view', NULL, TRUE);
      $response->addCacheableDependency($field_access);

      if (!$field_access->isAllowed()) {
        $entity->set($field_name, NULL);
      }
    }

    return $response;
  }

  /**
   * Vocabularies list endpoint.
   */
  public function vocabulary() {
    $vocabularies = [];
    $facets = $this->mappingInformation->getInternalTypes('taxonomy');
    foreach ($facets as $facet) {
      $vocabularies[] = $this->mappingInformation->getPublicDataType($facet['entity_type'], $facet['bundle']) ?? $facet['bundle'];
    }

    // @todo cachable dependency on the configuration?
    $response = new ResourceResponse($vocabularies, 200);

    return $response;
  }

  /**
   * Vocabulary Taxonomy endpoint.
   */
  public function taxonomies() {
    $facets = $this->mappingInformation->getInternalTypes('taxonomy');
    $vocabulary = $this->request->query->get('vocabulary');
    $facets_lookup = array_column($facets, 'entity_type', 'bundle');
    if (!isset($facets_lookup[$vocabulary])) {
      throw new NotFoundHttpException();
    }

    $entity_type = $facets_lookup[$vocabulary];
    $taxonomy_query = $this->entityTypeManager()->getStorage($entity_type)->getQuery();
    $taxonomy_bundle = $this->entityTypeManager()->getStorage($entity_type)->getEntityType()->getKey('bundle');
    assert($taxonomy_query instanceof QueryInterface);
    $taxonomy_query->condition($taxonomy_bundle, $vocabulary);
    if ($entity_type == 'taxonomy_term') {
      if ($this->request->query->get('root_only')) {
        $taxonomy_query->notExists('parent');
      }
      elseif ($parent_id = $this->request->query->get('parent_id')) {
        // @todo machine_name id for controlled vocabulary?
        $taxonomy_query->condition('parent:id', $parent_id);
      }
    }
    $this->initializePager($taxonomy_query, $this->request->query);
    $terms = $taxonomy_query->execute();
    if ($terms) {
      $terms = $this->entityTypeManager()->getStorage($entity_type)->loadMultiple($terms);
    }

    $response_array = [];
    $response_array = $this->outputPager();
    $response_array['content'] = $terms;

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->setCacheTags([$entity_type . '_list']);
    $cache_metadata->setCacheContexts(['url.query_args:vocabulary']);

    $response = new ResourceResponse($response_array, 200);
    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

}
