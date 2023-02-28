<?php

namespace Drupal\Tests\localgov_directories\Functional;

use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests facets on a directory channel as a filter.
 *
 * @group localgov_directories
 */
class FacetsTest extends BrowserTestBase {

  use NodeCreationTrait;
  use CronRunTrait;

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
    'block',
    'localgov_search',
    'localgov_search_db',
    'facets',
    'localgov_directories',
    'localgov_directories_db',
    'localgov_directories_page',
  ];

  /**
   * Test facets filter with And groups.
   */
  public function testFacetsFilters() {

    // Set up admin user.
    $admin_user = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'administer blocks',
    ]);

    // Place the facet block.
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('facet_block:localgov_directories_facets', []);
    $this->drupalLogout($admin_user);

    // Set up facet types.
    $facet_types = [
      'Group 1 ' . $this->randomMachineName(8),
      'Group 2 ' . $this->randomMachineName(8),
    ];
    foreach ($facet_types as $type_id) {
      $type = LocalgovDirectoriesFacetsType::create([
        'id' => $type_id,
        'label' => $type_id,
      ]);
      $type->save();
      $facet_type_entities[] = $type;
    }

    // Set up facets.
    $facets = [
      [
        'bundle' => $facet_types[0],
        'title' => 'Facet 1 ' . $this->randomMachineName(8),
      ],
      [
        'bundle' => $facet_types[0],
        'title' => 'Facet 2 ' . $this->randomMachineName(8),
      ],
      [
        'bundle' => $facet_types[1],
        'title' => 'Facet 3' . $this->randomMachineName(8),
      ],
      [
        'bundle' => $facet_types[1],
        'title' => 'Facet 4 ' . $this->randomMachineName(8),
      ],
    ];
    foreach ($facets as $facet_item) {
      $facet = LocalgovDirectoriesFacets::create($facet_item);
      $facet->save();
      $facet_entities[] = $facet;
    }
    $facet_labels = array_column($facets, 'title');

    // Set up a directory channel and assign the facets to it.
    $body = [
      'value' => 'Science is the search for truth, that is the effort to understand the world: it involves the rejection of bias, of dogma, of revelation, but not the rejection of morality.',
      'summary' => 'One of the greatest joys known to man is to take a flight into ignorance in search of knowledge.',
    ];

    $channel_node = $this->createNode([
      'title' => 'Directory channel',
      'type' => 'localgov_directory',
      'status' => NodeInterface::PUBLISHED,
      'body' => $body,
      'localgov_directory_channel_types' => [
        [
          'target_id' => 'localgov_directories_page',
        ],
      ],
      'localgov_directory_facets_enable' => [
        [
          'target_id' => $facet_types[0],
        ],
        [
          'target_id' => $facet_types[1],
        ],
      ],
    ]);

    // Set up some directory entires.
    $directory_nodes = [
      // Entry 1 has facet 1 only.
      [
        'title' => 'Entry 1 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $channel_node->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $facet_entities[0]->id(),
          ],
        ],
      ],
      [
        // Entry 2 has facet 2 only.
        'title' => 'Entry 2 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $channel_node->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $facet_entities[1]->id(),
          ],
        ],
      ],
      // Entry 3 has facet 1 and 3.
      [
        'title' => 'Entry 3 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $channel_node->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $facet_entities[0]->id(),
          ],
          [
            'target_id' => $facet_entities[2]->id(),
          ],
        ],
      ],
      // Entry 4 has all facets.
      [
        'title' => 'Entry 4 ' . $this->randomMachineName(8),
        'type' => 'localgov_directories_page',
        'status' => NodeInterface::PUBLISHED,
        'localgov_directory_channels' => [
          [
            'target_id' => $channel_node->id(),
          ],
        ],
        'localgov_directory_facets_select' => [
          [
            'target_id' => $facet_entities[0]->id(),
          ],
          [
            'target_id' => $facet_entities[1]->id(),
          ],
          [
            'target_id' => $facet_entities[2]->id(),
          ],
          [
            'target_id' => $facet_entities[3]->id(),
          ],
        ],
      ],
    ];

    foreach ($directory_nodes as $node) {
      $this->createNode($node);
    }

    // Get titles for comparison.
    $node_titles = array_column($directory_nodes, 'title');

    // Run cron so the directory entires are indexed.
    $this->cronRun();

    // Check facets and check the right entries are shown.
    $directory_url = $channel_node->toUrl()->toString();
    $this->drupalGet($directory_url);

    // Initially all four should be avalible.
    $this->assertSession()->pageTextContains($node_titles[0]);
    $this->assertSession()->pageTextContains($node_titles[1]);
    $this->assertSession()->pageTextContains($node_titles[2]);
    $this->assertSession()->pageTextContains($node_titles[3]);

    // Facet 1.
    // Click facet 1, should show entry 1, 3 and 4.
    $this->getSession()->getPage()->clickLink($facet_labels[0]);
    $this->assertSession()->pageTextContains($node_titles[0]);
    $this->assertSession()->pageTextNotContains($node_titles[1]);
    $this->assertSession()->pageTextContains($node_titles[2]);
    $this->assertSession()->pageTextContains($node_titles[3]);

    // Facet 1 OR Facet 2.
    // Click facet 2 (with 1 still clicked), should show entry 1, 2, 3 and 4.
    $this->getSession()->getPage()->clickLink($facet_labels[1]);
    $this->assertSession()->pageTextContains($node_titles[0]);
    $this->assertSession()->pageTextContains($node_titles[1]);
    $this->assertSession()->pageTextContains($node_titles[2]);
    $this->assertSession()->pageTextContains($node_titles[3]);

    // Facet 1 AND Facet 3.
    // Click facet 2 to deselect, click facet 3 (with 1 still clicked),
    // should show entry 3 and 4.
    $this->getSession()->getPage()->clickLink($facet_labels[1]);
    $this->getSession()->getPage()->clickLink($facet_labels[2]);
    $this->assertSession()->pageTextNotContains($node_titles[0]);
    $this->assertSession()->pageTextNotContains($node_titles[1]);
    $this->assertSession()->pageTextContains($node_titles[2]);
    $this->assertSession()->pageTextContains($node_titles[3]);

    // Facet 1 AND (Facet 3 OR Facet 4).
    // Click facet 4 (with 1 and 3 still clicked),
    // should show entry 3 and 4.
    $this->getSession()->getPage()->clickLink($facet_labels[3]);
    $this->assertSession()->pageTextNotContains($node_titles[0]);
    $this->assertSession()->pageTextNotContains($node_titles[1]);
    $this->assertSession()->pageTextContains($node_titles[2]);
    $this->assertSession()->pageTextContains($node_titles[3]);

    // Facet 1 AND Facet 4.
    // Click facet 3 to deselect (with 1 and 4 still clicked),
    // should show entry 4 only.
    $this->getSession()->getPage()->clickLink($facet_labels[2]);
    $this->assertSession()->pageTextNotContains($node_titles[0]);
    $this->assertSession()->pageTextNotContains($node_titles[1]);
    $this->assertSession()->pageTextNotContains($node_titles[2]);
    $this->assertSession()->pageTextContains($node_titles[3]);

    // (Facet 1 OR Facet 2) AND (Facet 3 OR Facet 4).
    // Click facet 2 and 3 (with 1 and 4 still clicked),
    // all facets selected, but should only show entry 3 and 4.
    $this->getSession()->getPage()->clickLink($facet_labels[1]);
    $this->getSession()->getPage()->clickLink($facet_labels[2]);
    $this->assertSession()->pageTextNotContains($node_titles[0]);
    $this->assertSession()->pageTextNotContains($node_titles[1]);
    $this->assertSession()->pageTextContains($node_titles[2]);
    $this->assertSession()->pageTextContains($node_titles[3]);
  }

}
