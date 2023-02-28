## What is it?
This is **not** a standard location for Drupal's config files.  As such these config files will not be imported at module install time.  Instead config files in this directory are added programmatically.

## Config files
- facets.facet.localgov_directories_facets.yml: Added when the **localgov_directory_facets_select** Facet selection field is added to a Directory entry content type for the first time. @see localgov_directories_create_dir_facet()
