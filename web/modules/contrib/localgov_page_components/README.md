# LocalGovDrupal Page Components

Reusable paragraphs library for the LocalGovDrupal distribution.

## What's in it?
### A node field
Provides the **localgov_page_components** node field.  This field can be used to add new Page components or select an existing one within a node.  The recommended field widget is **Entity browser** which should use the *Page component* browser.

This field is currently used in the localgov_services_page content type.  But it can be used in any other content type.

### LinkIt integration
The [LinkIt](https://www.drupal.org/project/linkit) module can use URLs belonging to localgov_link and localgov_contact Page components when the [localgov_paragraphs](https://packagist.org/packages/localgovdrupal/localgov_paragraphs) module is available.  Setup steps follow:
- Access the LinkIt profile configuration page from */admin/config/content/linkit*.
- Select the *Manage matchers* operation for the **Default** profile.  This should take you to */admin/config/content/linkit/manage/default/matchers*.
- Click *Add matcher* which should land you at */admin/config/content/linkit/manage/default/matchers/add*.
- Select **Page components** as the matcher.  *Save and continue*.  This should present the *Page components* matcher edit form.
- Select *Link* and *Contact* as *Bundle restrictions*.  Other Paragraph types are unsupported at the moment.
- *Limit search results* to 20.
- Select the *Group by bundle* checkbox within *Bundle grouping*.
- Select *Page components* from the **Substitution Type** dropdown within *URL substitution*.
- *Save changes*.
- Suggestions provided by LinkIt should now include localgov_link and localgov_contact Page components.

## Known issues
Some Page component edit forms (e.g. localgov_documents) try to open a modal.  This leads to a modal within a modal scenario which doesn't work.  The work around is to edit such Page components from their own edit page.  These can be looked up from  */admin/content/paragraphs*.
