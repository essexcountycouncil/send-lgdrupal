<?php

namespace Drupal\Tests\localgov_services_sublanding\Functional;

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests Topic List Builder Paragraph type.
 *
 * @group localgov_services
 */
class TopicListBuilderTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_services_sublanding',
  ];

  /**
   * Test topic list builder functionality.
   */
  public function testTopicListBuilderParagraphDisplay() {

    // Create a sub-landing page.
    $page = $this->createNode([
      'type' => 'localgov_services_sublanding',
      'title' => 'Test sub-landing page.',
      'body' => [
        'summary' => 'Test sub-landing page text',
        'value' => '',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Check with term and external link.
    $topic_name = $this->randomMachineName(8);
    $topic = Term::create([
      'name' => $topic_name,
      'vid' => 'localgov_topic',
    ]);
    $topic->save();
    $tlb_term = Paragraph::create([
      'type' => 'topic_list_builder',
      'topic_list_term' => ['target_id' => $topic->id()],
      'topic_list_links' => [
        'uri' => 'https://example.com/',
        'title' => 'External link text',
      ],
    ]);
    $tlb_term->save();
    $page->localgov_topics->appendItem($tlb_term);
    $page->save();
    $this->drupalGet('/node/' . $page->id());
    $this->assertSession()->pageTextContains($topic_name);
    $this->assertSession()->pageTextContains('External link text');
    $this->assertSession()->responseContains('href="https://example.com/"');

    // Check with header and link.
    $service_path = '/' . $this->randomMachineName(8);
    $service_page_summary = 'Test services page summary';
    $service_page = $this->createNode([
      'type' => 'localgov_services_page',
      'title' => 'Test services page',
      'body' => [
        'summary' => $service_page_summary,
        'value' => 'Test services page text',
      ],
      'status' => NodeInterface::PUBLISHED,
      'path' => ['alias' => $service_path],
    ]);
    $header = $this->randomMachineName(12);

    $internal_link_title = 'Example internal link';
    $ignored_node_link_title = 'Example ignored node link title';
    $tlb_header = Paragraph::create([
      'type' => 'topic_list_builder',
      'topic_list_header' => ['value' => $header],
      'topic_list_links' => [
        [
          // This internal link will not be treated as an entity link.  It will
          // produce a link but not any teaser text.
          'uri' => 'internal:' . $service_path,
          'title' => $internal_link_title,
        ],
        [
          // This entity link should produce a link+teaser combination.
          'uri' => "entity:node/{$service_page->id()}",
          'title' => $ignored_node_link_title,
        ],
      ],
    ]);
    $tlb_header->save();
    $page->localgov_topics->appendItem($tlb_header);
    $page->save();
    $this->drupalGet('/node/' . $page->id());
    $this->assertSession()->pageTextContains($header);
    $this->assertSession()->pageTextContains($internal_link_title);
    $this->assertSession()->responseContains('href="' . $service_path . '"');

    // Entity links always use the entity title.
    $this->assertSession()->pageTextNotContains($ignored_node_link_title);
    // Node links are followed by their teaser text.
    $this->assertSession()->pageTextContains($service_page_summary);
  }

}
