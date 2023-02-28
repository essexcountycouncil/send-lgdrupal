# LocalGov Drupal News

Provides the pages and navigation for presenting news articles. A part of the LocalGovDrupal distribution.

## What is in it?
- Content types:
  - News article - a single news article, optionally categorised;
  - Newsroom - a landing page to list and feature news articles.
- Blocks:
  - News search form;
  - Facets - by default date and category.

## Install process
- Standard Drupal module installation process applies.

- By default the search and facet blocks for news are shown in the view mode for the newsroom, and as blocks on all pages under the `news/*` path if you have the localgov_base theme installed. Alternatively, add, or change the configuration for, these three blocks from the Drupal block layout admin page.

## Usage
- Create a Newsroom
  - Create one, or more, newsrooms for articles to go into.
  - A newsroom has a field in which it is possible to select 3 featured articles.
  - The Featured News block shows up to 3 featured articles - if there are fewer than 3 explicitly featured articles the remainder will be filled by the latest promoted articles (if any).
  - The Article List block will show 10 articles per page, excluding those in the featured block.
  - The limits (3 and 10) can be changed in the localgov_news_list view on the all_news and featured_news displays.
- Add news articles. By default:
  - The Categories field uses the LocalGov Topics vocabulary. Edit the field to use alternative or additional vocabularies.
  - Image is a required field - authors can upload a new image or select an image from the media library.
  - Article nodes are not promoted - see the Featured News section below.
  - Article aliases are: [node:localgov_newsroom:entity:url:relative]/[node:localgov_news_date:date:html_year]/[node:title] thus prefacing the path with that of their newsroom, followed by year and sanitised title.

## Structured data
- The Schema.org Metatag module is used to generate structured data for individual news articles. This is rendered as JSON LD in the `<head>` element.

