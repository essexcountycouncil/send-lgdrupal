# LocalGov Search

Default sitewide search implementation for LocalGov Drupal.

Uses the Search API module to index all content on the site and provides a search
page (at /search) and a search block that is placed in the site header by default.

The Search API database backend is enabled when installing the module, but this can
be replaced by something else if desired.

All content types are added to the search index when the module is installed and
new content types are automatically added when they are created. If a content type
shouldn't be part of the search then this will need to be manually removed from the
search index (admin/config/search/search-api/index/localgov_sitewide_search/edit).

Content is indexed using the 'Search index' display mode and displayed using the
'Search result highlighting input' display mode. To change what is indexed and how
the results are displayed can be done by adjusting these display modes on the
desired content type.
