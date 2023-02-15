<?php

namespace Drupal\Tests\localgov_search_db\Functional;

use Drupal\Tests\localgov_search\Functional\SitewideSearchBase;

/**
 * Test search to check sitewide search integration.
 *
 * @group localgov_search
 */
class SitewideSearchTest extends SitewideSearchBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_search',
    'localgov_search_db',
    'big_pipe',
  ];

}
