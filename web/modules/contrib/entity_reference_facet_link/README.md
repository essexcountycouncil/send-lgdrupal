Entity Reference Facet Link
===========================
Entity Reference Facet Link is a Drupal 8 module that provides a formatter
plugin for entity reference fields, including taxonomy term reference fields.
It will allow you to display those entity references as links to a faceted
search page.

Here is an example use case:
Say you have a faceted search page, for instance one created using the Views;
[Search API](https://drupal.org/project/search_api); and
[Facets](https://drupal.org/project/facets) modules, that displays nodes
containing a taxonomy field.  That taxonomy field is used as a facet on your
search page.  If you went to a node and clicked on the link provided by the
default formatter for the taxonomy term, it would take you to that term's
default view page.  This may be bad for usability.  Instead, you may want that
term to link back to the search page which is filtered by that facet.  This
module will give you that ability.

Installation and Setup
----------------------
Setup of Entity Reference Facet Links couldn't be simpler.  It is dependent on
the Facets module and will not work with other facet provider modules, if any
exist.  These instructions will assume that if you're interested in this module,
then you already have a search page with facets configured for it.  If not,
there are online tutorials that will help you set one up.

1. Install this module through the usual means, mainly with Composer.

2. Go to the page where you want to configure the field's display, such as a
content entity's Manage display page or a view.

3. For your faceted field, choose "Facet link" from the select list under
Format.

4. Edit the Format's settings.  Choose the facet you want to use from the select
list.

5. Save the settings.

That's it!  The facet link plugin takes care of everything else for you.  You
don't need to know anything about the facet other than its name.  The plugin
will pull everything it needs to know about the selected facet's configuration
automatically via the Drupal 8 APIs.

The format's settings form will narrow the list of of selectable facets down to
ones that match the field you're configuring.  If you have multiple search pages
with the same field added as a facet to more than one, then you'll have to pick
the facet for the page to which you want the field to link.  In this case, you
may want to give the facets unique names so you can tell them apart in the
UI.

FAQs
----
* **Q:** Will this module work with other facet URL processors than the default,
  for instance
  [Facets Pretty Paths](https://drupal.org/project/facets_pretty_paths)?

  **A:** Yes!  The formatter will use the exact same URL processor to generate
  its links that your facet is using.  You can change the processor at any time
  and the field links will automatically update.

* **Q:** Why didn't the field links update when I changed the path of my facet
  source (e.g. view)?

  **A:** There was no way that I could find to invalidate the field's display
  cache when the source was updated.  My plan is to bring this up with the
  Search API and Facets maintainers.  In the meantime, you'll have to clear your
  site cache manually if you update your source's path.

* **Q:** Is this module a Drupal 8 port of
  [Facet link formatter](https://drupal.org/project/facet_link_formatter)?

  **A:** No, I started developing this module before searching to see if someone
  else had done this before, *which you should never, ever do!*  So this was
  developed entirely from scratch.

  I have contacted the maintainer of Facet link formatter and found out there is
  no D8 port planned for that module.  ERFL is now the recommended replacement
  for FLF for sites that are upgrading to Drupal 8.  I'm not planning on writing
  any sort of upgrade path because the field settings are different.  FLF
  required you to give it the path of the search page.  ERFL requires you to
  specify the facet ID, which is a config entity.  There's no way to map one to
  the other.
