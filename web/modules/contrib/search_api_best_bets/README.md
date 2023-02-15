## CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Developers
 * Sponsors
 * Maintainers

## INTRODUCTION

This module let editors configure search terms per entity that should trigger
elevate (best bets) or exclude when users is searching the site.

The entity form integration is using a custom form field that can be added to
all entity bundles in Drupal.

The search backend integration is using Search API processor plugin for looking
up entities for elevation or exclusion during the search query generation. The
per backend type query manipulation is handled in Best Bets QueryHandler
plugins.

The module comes with a QueryHandler plugin for Solr utilizing
elevateIds and exlcudeIds search query parameters in Apache Solr. Plugins
for other backends can be implemented in third part modules.

Notice: the module does, at the moment, not support generation of elevate.xml
for Apache Solr. Or similar solutions for other search backends. It is fully
search query based!

## REQUIREMENTS

 * Search API (https://www.drupal.org/project/search_api)
 * Apache Solr integration:
 ** Search API Solr (https://www.drupal.org/project/search_api_solr)
 ** Apache Solr 4.7+ (older versions does not support elevateIds and exlcudeIds)

## INSTALLATION

 * Install as you would normally install a contributed drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

## CONFIGURATION

After installation add a field of the type "Search API Best Bets" to one or more
entity bundles (e.g. content types) where you want best bets support.

When the field has been added go to your Search API index and choose the
Processors tab. Enable the processor "Search API Best Bets" and edit the
processor settings. Choose the field you just added to your entity bundles
and choose a Query handler plugin. Only Solr is supported by the module it self.

In the processor settings is it also possible to configure how the elevated
flag on the item object should be set. It can either be set based on data
received back from the search backend (e.g. field [elevated] in Solr) - this
is the option "Query handler plugin", or it can be set locally in Drupal based
on the elevated items send to the search back as part of the search query. The
former is not always working as expected in Solr, and in such cases can the
latter option be used.

## DEVELOPERS

The Search API Best Bets modules provides the following ways for developers to
extend the functionality:

- Plugins
  Best Bets Query Handler plugin - see the annotation and the Solr plugin:
  - Drupal\search_api_best_bets\Annotation\SearchApiBestBetsQueryHandler
  - Drupal\search_api_best_bets\Plugin\search_api_best_bets\query_handler\Solr
- Theming
  Search API Pages result template (search-api-page-result.html.twig)
  - class search-api-elevated is added to both title and content attributes.
  - variable elevated is TRUE if the item was elevated.
  Other templates / theming
  - Get the elevate status from $item->getExtraData('elevated').

## SPONSORS

 * FFW - https://ffwagency.com

## MAINTAINERS

Current maintainers:
 * Jens Beltofte (beltofte) - https://drupal.org/u/beltofte
 * Stephen Mustgrave (smustgrave) - https://drupal.org/u/smustgrave
