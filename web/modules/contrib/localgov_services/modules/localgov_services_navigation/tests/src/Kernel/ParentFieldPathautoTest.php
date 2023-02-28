<?php

namespace Drupal\Tests\localgov_services_navigation\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Kernel test check Services Pathauto.
 *
 * @group pathauto
 */
class ParentFieldPathautoTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;
  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'link',
    'user',
    'node',
    'path',
    'path_alias',
    'pathauto',
    'token',
    'filter',
    'language',
    'localgov_services_navigation',
  ];

  /**
   * Service Landing page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $serviceLanding;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'filter',
      'pathauto',
      'system',
      'node',
      'localgov_services_navigation',
    ]);

    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Create a content type to put into services.
    $this->createContentType(['type' => 'localgov_services_landing']);
    // Create a service.
    $this->serviceLanding = $this->createNode([
      'title' => 'Landing Page 1',
      'type' => 'localgov_services_landing',
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Create a content type to put into services.
    $this->createContentType(['type' => 'page']);
    $this->createPattern('node', '/content/[node:title]');
  }

  /**
   * Test programmatic parent addition.
   */
  public function testServiceParentAddition() {
    // Test without the services field.
    $node = $this->createNode([
      'title' => 'Page 1',
      'type' => 'page',
    ]);
    $this->assertEntityAlias($node, '/content/page-1');

    // Add services field.
    $field_name = 'localgov_services_parent';
    $this->createEntityReferenceField(
      'node',
      'page',
      $field_name,
      $field_name,
      'node',
      'localgov_services',
      [
        'target_bundles' => [
          'localgov_services_landing',
        ],
      ]
    );

    // Test with services field, but not completed.
    $node = $this->createNode([
      'title' => 'Page 2',
      'type' => 'page',
    ]);
    $this->assertEntityAlias($node, '/content/page-2');

    // Test with services field, completed.
    $node = $this->createNode([
      'title' => 'Page 3',
      'type' => 'page',
      'localgov_services_parent' => ['target_id' => $this->serviceLanding->id()],
    ]);
    $this->assertEntityAlias($node, $this->serviceLanding->toUrl()->toString() . '/content/page-3');
  }

}
