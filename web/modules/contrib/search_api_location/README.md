# Search API Location

This module adds support for indexing location values provided by the geofield
module and subsequently filtering and/or sorting on them, provided the service
class supports this.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/search_api_location).

To submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/search_api_location).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

For this module to have any effect, You need to enable search_api module. Keep
in mind the backend service class of the search_api has to support those data
type as well. The [Search API Solr module](https://www.drupal.org/project/search_api_solr)
module is known to fully support this feature. [Elasticsearch Connector](https://www.drupal.org/project/elasticsearch_connector)
may work but needs additional patching and is not fully tested. See issue
[#3116153](https://www.drupal.org/i/3116153) for more information.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules),
Ensure the Toolbar module is installed.


## Configuration

1. After enabling search_api_location go to
   /admin/config/search/search-api/index/YOUR_INDEX_NAME/fields

2. Click on 'add fields' and select the geofield which you want to index.
3. Note the geofield must have stored a lat/lon pair value.
4. Then select type as Latitude/longitude to work with search_api_location_views
   and/or Recursive Prefix Tree to work with fcets_map_widget.


## Maintainers

- Thomas Seidl - [drunken monkey](https://www.drupal.org/u/drunken-monkey)
- Mattias Michaux - [mollux](https://www.drupal.org/u/mollux)
- Yuriy Gerasimov - [ygerasimov](https://www.drupal.org/u/ygerasimov)
- Nick Veenhof - [Nick_vh](https://www.drupal.org/u/nick_vh)
- Joris Vercammen - [borisson_](https://www.drupal.org/u/borisson_)
- Jeroen Tubex - [JeroenT](https://www.drupal.org/u/jeroent)
