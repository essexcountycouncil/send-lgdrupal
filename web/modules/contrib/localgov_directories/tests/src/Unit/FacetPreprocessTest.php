<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_directories\Unit;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;

use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\localgov_directories\DirectoryExtraFieldDisplay;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the DirectoryExtraFieldDisplay class.
 */
class FacetPreprocessTest extends UnitTestCase {

  /**
   * Tests for DirectoryExtraFieldDisplay::preprocessFacetList().
   *
   * Ensures that the *Facet types* are sorted according to their weights and
   * labels.
   *
   * ## Test data
   * Facet item | Facet type | Facet type weight
   * -----------+------------+------------------
   * zero       | foo        | 30
   * one        | bar        | 10
   * two        | baz        | 20
   * three      | qux        | 0
   * four       | jar        | 0
   *
   * The expected output order of the Facet types is: jar, qux, bar, baz, foo.
   */
  public function testFacetTypeSorting() {

    $test_obj = new DirectoryExtraFieldDisplay($this->mockEntityTypeManager, $this->mockEntityRepository, $this->mockEntityFieldManager, $this->mockBlockPluginManager, $this->mockFormBuilder);

    $facet_tpl_variables = [
      'items' => [
        ['value' => ['#attributes' => ['data-drupal-facet-item-value' => 'zero']]],
        ['value' => ['#attributes' => ['data-drupal-facet-item-value' => 'one']]],
        ['value' => ['#attributes' => ['data-drupal-facet-item-value' => 'two']]],
        ['value' => ['#attributes' => ['data-drupal-facet-item-value' => 'three']]],
        ['value' => ['#attributes' => ['data-drupal-facet-item-value' => 'four']]],
      ],
    ];
    $test_obj->preprocessFacetList($facet_tpl_variables);

    $sorted_facet_types = array_keys($facet_tpl_variables['items']);
    $expected_sorted_facet_types = ['jar', 'qux', 'bar', 'baz', 'foo'];
    $this->assertSame($expected_sorted_facet_types, $sorted_facet_types);
  }

  /**
   * Create the mock dependencies.
   *
   * Initialize everything needed to create a DirectoryExtraFieldDisplay object.
   *
   * @see DirectoryExtraFieldDisplay::__construct()
   */
  public function setup(): void {

    // Facet items.
    $facet_zero = $this->createMock(LocalgovDirectoriesFacets::class);
    $facet_zero->expects($this->any())->method('bundle')->willReturn('foo');
    $facet_one = $this->createMock(LocalgovDirectoriesFacets::class);
    $facet_one->expects($this->any())->method('bundle')->willReturn('bar');
    $facet_two = $this->createMock(LocalgovDirectoriesFacets::class);
    $facet_two->expects($this->any())->method('bundle')->willReturn('baz');
    $facet_three = $this->createMock(LocalgovDirectoriesFacets::class);
    $facet_three->expects($this->any())->method('bundle')->willReturn('qux');
    $facet_four = $this->createMock(LocalgovDirectoriesFacets::class);
    $facet_four->expects($this->any())->method('bundle')->willReturn('jar');

    $mock_facet_storage = $this->createMock(EntityStorageInterface::class);
    $mock_facet_storage->expects($this->any())
      ->method('load')
      ->will($this->returnValueMap([
        ['zero', $facet_zero],
        ['one', $facet_one],
        ['two', $facet_two],
        ['three', $facet_three],
        ['four', $facet_four],
      ]));

    // Facet types.
    $facet_type_foo = $this->createMock(LocalgovDirectoriesFacetsType::class);
    $facet_type_foo->expects($this->any())->method('get')->willReturn(30);
    $facet_type_foo->expects($this->any())->method('label')->willReturn('foo');
    $facet_type_bar = $this->createMock(LocalgovDirectoriesFacetsType::class);
    $facet_type_bar->expects($this->any())->method('get')->willReturn(10);
    $facet_type_bar->expects($this->any())->method('label')->willReturn('bar');
    $facet_type_baz = $this->createMock(LocalgovDirectoriesFacetsType::class);
    $facet_type_baz->expects($this->any())->method('get')->willReturn(20);
    $facet_type_baz->expects($this->any())->method('label')->willReturn('baz');
    $facet_type_qux = $this->createMock(LocalgovDirectoriesFacetsType::class);
    $facet_type_qux->expects($this->any())->method('get')->willReturn(0);
    $facet_type_qux->expects($this->any())->method('label')->willReturn('qux');
    $facet_type_jar = $this->createMock(LocalgovDirectoriesFacetsType::class);
    $facet_type_jar->expects($this->any())->method('get')->willReturn(0);
    $facet_type_jar->expects($this->any())->method('label')->willReturn('jar');

    $mock_facet_type_storage = $this->createMock(EntityStorageInterface::class);
    $mock_facet_type_storage->expects($this->any())
      ->method('load')
      ->will($this->returnValueMap([
        ['foo', $facet_type_foo],
        ['bar', $facet_type_bar],
        ['baz', $facet_type_baz],
        ['qux', $facet_type_qux],
        ['jar', $facet_type_jar],
      ]));

    // Finally, the dependencies.
    $this->mockEntityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->mockEntityTypeManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValueMap([
        ['localgov_directories_facets', $mock_facet_storage],
        ['localgov_directories_facets_type', $mock_facet_type_storage],
      ]));

    $this->mockEntityRepository = $this->createMock(EntityRepositoryInterface::class);
    $this->mockEntityRepository->expects($this->any())
      ->method('getTranslationFromContext')
      ->will($this->returnCallback(function ($arg) {
        return $arg;
      }));

    $this->mockEntityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->mockBlockPluginManager = $this->createMock(BlockManagerInterface::class);
    $this->mockFormBuilder        = $this->createMock(FormBuilderInterface::class);
  }

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $mockEntityTypeManager;

  /**
   * Mock entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $mockEntityRepository;

  /**
   * Mock entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $mockEntityFieldManager;

  /**
   * Mock block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $mockBlockPluginManager;

  /**
   * Mock form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $mockFormBuilder;

}
