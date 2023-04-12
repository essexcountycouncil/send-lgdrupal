# Search API Exclude Entity

This module is the Drupal 8 / Search API successor to Apache Solr Node Exclude.
It makes it possible to exclude nodes and other entities from being indexed in
search indexes configured using Search API framework.

Compared to the Drupal 7 / Apache Solr version is the new Drupal 8 version
using a custom field type, widget and formatter for controlling the exclude
status on entities. This gives some advantages:

- The field position, field label and description is configurable per
  entity * / bundle.
- It is possible to use multiple exclude fields on the same entity bundle,
  if multiple search systems or indexes is being used in the site.
- Views integration out of the box without custom plugins.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/search_api_exclude_entity).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/search_api_exclude_entity).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

- [Search API](https://www.drupal.org/project/search_api)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

- Add a `"Search API Exclude Entity"` field to the entity types / bundles that
- is going to use the exclude functionality.
- Add your search server and index in Search API.
- Enable the `"Search API Exclude Entity"` processor.
- In the processor settings enable the fields that should be used for
  controlling the entity exclude status in the active index.
  It is possible to have multiple entity types and fields enabled per index.


## Maintainers

- Jens Beltofte - [beltofte](https://drupal.org/u/beltofte)
- Stephen Mustgrave - [smustgrave](https://drupal.org/u/smustgrave)

**Sponsors by:-**

- [FFW](https://ffwagency.com)
