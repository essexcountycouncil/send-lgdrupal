# LocalGov Menu link group

## What is it?
This Drupal module allows you to bundle several menu links into a group.  This is useful when you want to breakdown a long list of menu links into several smaller groups for better user experience.

As an example, imagine having a long list of content types: Banana, Blackbird, Bat, Cat, Elephant, Magpie, Orange, and Watermelon.  If you are using [Admin Toolbar Extra Tools](https://www.drupal.org/project/admin_toolbar) or a similar module, these content types will appear as a flat list of menu links under Drupal's **Add content** menu item.  This module can help you group these menu links into severel smaller groups such as the following:
```
-- Add content
---- Birds
------ Blackbird
------ Magpie
---- Fruits
------ Banana
------ Orange
------ Watermelon
---- Mammals
------ Bat
------ Cat
------ Elephant
```
Here we have defined the Birds, Fruits, and Mammals groups and decided which menu links belong to each group.

This grouping functionality is not limited to content types only.  It can be applied to any menu link.

## How to use it
- Install the localgov_menu_link_group module in the usual way.
- Login as a site admin or any other user with the **administer site configuration** permission.
- Head to /admin/structure/menu/localgov_menu_link_group
- Add a new Menu link group.
- While adding the group, decide where in the menu tree this group should appear.  Choose your selection from the **Parent menu link** dropdown.
- Use the **Child menu links** dropdown to select the menu links that should come under this new group.
- Save the Group form.
- A menu link with your choosen group label should appear in the menu tree under your choosen parent menu link.  This menu link is not clickable.
- Hovering over this menu link should reveal its child menu links.

## Good to know
- If you have the [multiselect](https://www.drupal.org/project/multiselect) Drupal module installed, the *Child menu links* dropdown would make use of that resulting in better user experience.
- The **weight** value of each group can be adjusted in two ways:
  - From the Group add/edit form.
  - By drag-and-drop from the group listing page at /admin/structure/menu/localgov_menu_link_group
- The group configurations are fully exportable and importable like any other Drupal configuration file.
- By using the same Group label and parent menu link, multiple groups can be part of the same menu link group.  This could be useful when you are providing configration files from multiple modules.

## Todo
- Unit tests.
