<?php

namespace Drupal\Tests\preview_link\Functional;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\preview_link\Entity\PreviewLink;
use Drupal\Tests\BrowserTestBase;
use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Test access to preview pages with valid/invalid tokens.
 *
 * @group preview_link
 */
class PreviewLinkAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'preview_link',
  ];

  /**
   * Test access with tokens.
   */
  public function testPreviewFakeToken() {
    $account = $this->createUser([
      'view test entity',
    ]);
    $this->drupalLogin($account);

    $entity = EntityTestRev::create();
    $entity->save();
    $access = $entity->access('view', $account, TRUE);
    // Make sure the current user has access to the entity.
    $this->assertTrue($access->isAllowed());

    // The entity needs a preview link otherwise the access checker quits early.
    $this->getNewPreviewLinkForEntity($entity);

    // Create a temporary preview link entity to utilize whichever token
    // generation process is in use.
    $token = PreviewLink::create()->getToken();
    // Make sure the token is set.
    $this->assertIsString($token);
    $this->assertTrue(strlen($token) > 0);

    $url = Url::fromRoute('entity.entity_test_rev.preview_link', [
      'entity_test_rev' => $entity->id(),
      'preview_token' => $token,
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Ensure access is allowed with a real token.
   */
  public function testPreviewRealToken() {
    $account = $this->createUser([
      'view test entity',
    ]);
    $this->drupalLogin($account);

    $entity = EntityTestRev::create();
    $entity->save();
    $access = $entity->access('view', $account, TRUE);
    // Make sure the current user has access to the entity.
    $this->assertTrue($access->isAllowed());

    // Create a temporary preview link entity to utilize whichever token
    // generation process is in use.
    $preview = $this->getNewPreviewLinkForEntity($entity);
    $token = $preview->getToken();

    $url = Url::fromRoute('entity.entity_test_rev.preview_link', [
      'entity_test_rev' => $entity->id(),
      'preview_token' => $token,
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test the preview link routes based on the settings.
   */
  public function testPreviewLinkEnabledEntityTypesConfiguration() {
    $config = $this->config('preview_link.settings');

    $account = $this->createUser([
      'view test entity',
    ]);
    $this->drupalLogin($account);

    $entity = EntityTestRev::create();
    $entity->save();

    $preview = $this->getNewPreviewLinkForEntity($entity);
    $token = $preview->getToken();

    $url = Url::fromRoute('entity.entity_test_rev.preview_link', [
      'entity_test_rev' => $entity->id(),
      'preview_token' => $token,
    ]);

    // Allowed when entity types are empty.
    $config->set('enabled_entity_types', [])->save();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Forbidden if restricted by entity type.
    $config->set('enabled_entity_types', [
      'foo' => [],
    ])->save();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);

    // Allowed if entity type is in restricted list.
    $config->set('enabled_entity_types', [
      'foo' => [],
      'entity_test_rev' => [],
    ])->save();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Forbidden if bundle is specific and isn't present.
    $config->set('enabled_entity_types', [
      'foo' => [],
      'entity_test_rev' => [
        'foo',
      ],
    ])->save();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);

    // Allowed if bundle is specified and present.
    $config->set('enabled_entity_types', [
      'foo' => [],
      'entity_test_rev' => [
        'foo',
        'entity_test_rev',
      ],
    ])->save();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Get a saved preview link for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity.
   *
   * @return \Drupal\preview_link\Entity\PreviewLinkInterface|null
   *   The preview link, or null if no preview link generated.
   */
  protected function getNewPreviewLinkForEntity(ContentEntityInterface $entity) {
    /** @var \Drupal\preview_link\PreviewLinkStorage $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('preview_link');
    return $storage->createPreviewLinkForEntity($entity);
  }

}
