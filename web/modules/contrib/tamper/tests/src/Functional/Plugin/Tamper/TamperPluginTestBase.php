<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests configuring Tamper plugins in the UI.
 */
abstract class TamperPluginTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'tamper', 'tamper_test'];

  /**
   * The config entity to add third party settings to.
   *
   * @var \Drupal\entity_test\Entity\EntityTestWithBundle
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->entity = EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ]);
    $this->entity->save();
  }

  /**
   * Tests filling in a form.
   *
   * @param array $expected
   *   The expected saved configuration.
   * @param array $edit
   *   The values on the form.
   * @param array $errors
   *   The form validation errors displayed on the page.
   *
   * @dataProvider formDataProvider
   */
  public function testForm(array $expected, array $edit = [], array $errors = []) {
    $expected += [
      'id' => static::$pluginId,
    ];

    $this->drupalGet('/tamper_test/test/' . static::$pluginId);
    $this->submitForm($edit, 'Submit');

    // Check for texts on the page.
    $session = $this->assertSession();
    if (!empty($errors)) {
      $session->pageTextNotContains('Configuration saved.');
      foreach ($errors as $error) {
        $session->pageTextContains($error);
      }

      // Abort the test here.
      return;
    }
    else {
      $session->pageTextContains('Configuration saved.');
    }

    $this->entity = $this->reloadEntity($this->entity);
    $tampers = $this->entity->getThirdPartySetting('tamper_test', 'tampers');
    $this->assertSame($expected, $tampers[static::$pluginId]);

    // Flush cache in order for the entity to not get served from cache.
    drupal_flush_all_caches();
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Submit the form again with no values and assert that the plugin is still
    // configured the same.
    $this->submitForm([], 'Submit');
    $this->entity = $this->reloadEntity($this->entity);
    $tampers = $this->entity->getThirdPartySetting('tamper_test', 'tampers');
    $this->assertSame($expected, $tampers[static::$pluginId]);
  }

  /**
   * Data provider for ::testForm().
   */
  public function formDataProvider(): array {
    // Some plugins don't have special configuration.
    return [
      'no values' => [
        'expected' => [],
      ],
    ];
  }

  /**
   * Reloads an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reload.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ $storageEntityStorageInterface */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    return $storage->load($entity->id());
  }

}
