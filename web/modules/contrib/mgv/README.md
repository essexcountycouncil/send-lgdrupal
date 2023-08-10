# More Global Variables

This is a small module that gives users some global variables
that can be then printed in any twig template. For example, if
you wanted to print the current page title as the last menu in
a breadcrumb, you could print {{ global_variables.current_page_title }}
in breadcrumb.html.twig. It can also be printed in any other
template - html.html.twig, page.html.twig, node.html.twig,
field.html.twig, etc.

Table of Contents.
1. Paths
    1. Current Path - `{{ global_variables.current_path }}`
    2. Current Path Alias - `{{ global_variables.current_path_alias }}`
    3. Base URL - `{{ global_variables.base_url }}`
2. Current Items
    1. Current Page Title `{{ global_variables.current_page_title }}`
    2. Current Langcode `{{ global_variables.current_langcode }}`
    3. Current Langname `{{ global_variables.current_langname }}`
3. Site Information Page Global variables
    1. Site Name - `{{ global_variables.site_name }}`
    2. Site Slogan - `{{ global_variables.site_slogan }}`
    3. Site Mail - `{{ global_variables.site_mail }}`
    4. Site Logo - `{{ global_variables.logo }}`
4. Social Sharing
    1. Twitter - `{{ global_variables.social_sharing.twitter }}`
    2. Facebook - `{{ global_variables.social_sharing.facebook }}`
    3. LinkedIn - `{{ global_variables.social_sharing.linkedin }}`
    4. Email - `{{ global_variables.social_sharing.email }}`
    5. WhatsApp - `{{ global_variables.social_sharing.whatsapp }}`

For a full description of the module, visit the
[project page](https://www.drupal.org/project/mgv).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/mgv).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

Supported version of the Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Module does not require any kind of configuration.


## Maintainers

- Mark Conroy - [markconroy](https://www.drupal.org/u/markconroy)
- Oleh Vehera - [voleger](https://www.drupal.org/u/voleger)
