<?php

namespace Drupal\Tests\preview_link\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Base class for preview link testing.
 */
abstract class PreviewLinkBase extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'filter', 'preview_link'];

  /**
   * The preview link storage.
   *
   * @var \Drupal\preview_link\PreviewLinkStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('preview_link');
    $this->installConfig(['node', 'filter']);
    $this->createContentType(['type' => 'page']);
    $this->storage = $this->container->get('entity_type.manager')->getStorage('preview_link');
  }

}
