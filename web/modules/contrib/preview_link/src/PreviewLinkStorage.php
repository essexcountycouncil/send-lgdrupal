<?php

namespace Drupal\preview_link;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preview Link entity storage.
 */
class PreviewLinkStorage extends SqlContentEntityStorage implements PreviewLinkStorageInterface {

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new PreviewLinkStorage.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend to be used.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, UuidInterface $uuid, TimeInterface $time) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager);
    $this->uuidService = $uuid;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('uuid'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewLinkForEntity(ContentEntityInterface $entity) {
    return $this->getPreviewLink($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewLink(ContentEntityInterface $entity) {
    $result = $this->loadByProperties([
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
    return $result ? array_pop($result) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createPreviewLinkForEntity(ContentEntityInterface $entity) {
    return $this->createPreviewLink($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function createPreviewLink($entity_type_id, $entity_id) {
    $preview_link = $this->create([
      'entity_id' => $entity_id,
      'entity_type_id' => $entity_type_id,
    ]);
    $preview_link->save();
    return $preview_link;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    return parent::create($values + [
      'token' => $this->generateUniqueToken(),
      'generated_timestamp' => $this->time->getRequestTime(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if ($entity->regenerateToken()) {
      $entity->setToken($this->generateUniqueToken());
    }
    return parent::save($entity);
  }

  /**
   * Gets the unique token for the link.
   *
   * This token is unique every time we generate a link, there is nothing
   * from the original entity involved in the token so it does not need to be
   * cryptographically secure, only sufficiently random which UUID is.
   *
   * @return string
   *   A unique identifier for this preview link.
   */
  protected function generateUniqueToken() {
    return $this->uuidService->generate();
  }

}
