<?php

namespace Drupal\localgov_directories_or;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enable using Directories 'content facets' for Open Referral taxonomy.
 */
class FacetMapping implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FacetMapping constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Add, remove, Open Referral Mappings for Facets.
   */
  public function synchroniseFacetMappings() {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $facet_storage = $this->entityTypeManager->getStorage('localgov_directories_facets_type');
    $mapping_storage = $this->entityTypeManager->getStorage('localgov_openreferral_mapping');

    // Iterate over all directories.
    // Check if they have Open Referral enabled Entries.
    // List all facet types associated with directories with Open Referral
    // entries.
    $openreferral_facets = [];
    $directory_query = $node_storage->getQuery()
      ->condition('type', 'localgov_directory')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();
    foreach ($node_storage->loadMultiple($directory_query) as $directory) {
      $openreferral_enabled = FALSE;
      foreach ($directory->get('localgov_directory_channel_types') as $item) {
        if ($mapping_storage->load('node.' . $item->target_id)) {
          $openreferral_enabled = TRUE;
          break;
        }
      }
      if ($openreferral_enabled) {
        foreach ($directory->get('localgov_directory_facets_enable') as $item) {
          $openreferral_facets[$item->target_id] = $item->target_id;
        }
      }
    }

    // Add or remove Open Referral mappings from facet types as required by
    // their listing in directories with Open Referral entries.
    $facet_query = $facet_storage->getQuery()
      ->execute();
    foreach ($facet_query as $facet_type) {
      if (in_array($facet_type, $openreferral_facets) &&
        !$mapping_storage->load('localgov_directories_facets.' . $facet_type)
      ) {
        $facet_mapping = $mapping_storage->create([
          'entity_type' => 'localgov_directories_facets',
          'bundle' => $facet_type,
          'public_type' => 'taxonomy',
          'property_mappings' => [
            'default' => [
              ['field_name' => 'title', 'public_name' => 'name'],
              ['field_name' => 'uuid', 'public_name' => 'id'],
            ],
          ],
        ]);
        $facet_mapping->save();
      }
      if (!in_array($facet_type, $openreferral_facets) &&
        ($facet_mapping = $mapping_storage->load('localgov_directories_facets.' . $facet_type))
      ) {
        $facet_mapping->delete();
      }
    }
  }

}
