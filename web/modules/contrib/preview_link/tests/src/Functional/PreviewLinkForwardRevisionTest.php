<?php

namespace Drupal\Tests\preview_link\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\preview_link\Entity\PreviewLink;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Test forward revisions are loaded.
 *
 * @group preview_link
 */
class PreviewLinkForwardRevisionTest extends BrowserTestBase {
  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'preview_link',
    'node',
    'text',
    'filter',
    'content_moderation',
    'paragraphs',
    'entity_reference_revisions',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->createEditorialWorkflow();
    $this->createContentType(['type' => 'page']);

    $workflow = Workflow::load('editorial');
    $workflow
      ->getTypePlugin()
      ->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
  }

  /**
   * Test the latest forward revision is loaded.
   */
  public function testForwardRevision() {
    $original_random_text = 'Original Title';
    $latest_random_text = 'Latest Title';

    // Create a node with some random text.
    $node = $this->createNode([
      'title' => $original_random_text,
      'moderation_state' => 'published',
    ]);

    // Create a forward revision with new text.
    $node->setTitle($latest_random_text);
    $node->moderation_state = 'draft';
    $node->save();

    // Create the preview link.
    $previewLink = PreviewLink::create([
      'entity_type_id' => 'node',
      'entity_id' => $node->id(),
    ]);
    $previewLink->save();

    // Visit the node and assert the original text.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextNotContains($latest_random_text);
    $this->assertSession()->pageTextContains($original_random_text);

    // Visit the preview link and assert the forward revision text.
    $this->drupalGet($previewLink->getUrl());
    $this->assertSession()->pageTextContains($latest_random_text);
    $this->assertSession()->pageTextNotContains($original_random_text);
  }

  /**
   * Tests draft revision with paragraph field.
   */
  public function testDraftRevisionWithParagraphField() {
    $this->setupParagraphTypeAndField();

    // Create a paragraph.
    $paragraph = Paragraph::create([
      'type' => 'section',
      'section' => 'A section title',
      'status' => 1,
    ]);
    $paragraph->save();

    // Reference the paragraph from the node.
    $node = $this->createNode([
      'title' => 'A draft with paragraph content',
      'paragraphs' => [$paragraph],
      'moderation_state' => 'draft',
    ]);

    // Create the preview link.
    $previewLink = PreviewLink::create([
      'entity_type_id' => 'node',
      'entity_id' => $node->id(),
    ]);
    $previewLink->save();

    // Login as user who can view unpublished content.
    $this->drupalLogin($this->drupalCreateUser(array_keys($this->container->get('user.permissions')
      ->getPermissions())));
    $this->drupalGet($node->toUrl());
    $assert = $this->assertSession();
    // Section title shown to admin on viewing draft.
    $assert->pageTextContains('A section title');

    // Now visit preview link as anonymous, and verify paragraph content is
    // shown.
    $this->drupalLogout();
    $this->drupalGet($previewLink->getUrl());
    $assert->pageTextContains('A section title');
  }

  /**
   * Sets up paragraph type and field.
   */
  protected function setupParagraphTypeAndField() {
    // Add a paragraph type.
    $paragraph_type = ParagraphsType::create([
      'id' => 'section',
      'label' => 'Section',
    ]);
    $paragraph_type->save();

    // Add a text field to the paragraph type.
    $storage = FieldStorageConfig::create([
      'entity_type' => 'paragraph',
      'type' => 'text',
      'field_name' => 'section',
    ]);
    $storage->save();

    $field = FieldConfig::create([
      'entity_type' => 'paragraph',
      'field_name' => 'section',
      'bundle' => 'section',
      'type' => 'text',
      'label' => 'Section',
    ]);
    $field->save();

    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'paragraphs',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'field_name' => 'paragraphs',
      'settings' => [
        'handler' => 'default:paragraph',
      ],
    ]);
    $field->save();

    $view_display = EntityViewDisplay::load('node.page.default')
      ->setComponent('paragraphs', ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();

    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'section',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('section', ['type' => 'text_default']);
    $view_display->save();
  }

}
