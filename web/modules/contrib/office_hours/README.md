# OFFICE HOURS

Office Hours creates a Field, that you can add to any entity (like a location,
a restaurant or a user) to represent "office hours" or "opening hours".

## GENERAL FEATURES

The widget provides:
- default weekly office hours (multi-value, per field instance).
- using 1, 2 or even more 'time slots' per day (thanks to jonhattan).
- 'allowed hours' restrictions;
- input validation;
- use of either a 24 or 12 hour clock;
- (as per v8.x-1.7) an ability to add 'Exception days' with calendar date
  instead of weekday.

The formatter provides o.a.:
- a 'Current status' indicator ('open now'/'closed now');
- options to show all/none/open/current days;
- options to group days (E.g., "Mon-Fri 12:00-22:00");
- customizable element separators to display the 'office hours' any way
  you want. (See below for details.)
- a drop-down formatter with current time,
  that shows all hours upon clicking on it. (v8.x-1.7)
- separate css class for the 'current' slot. (v8.x-1.7)

You can configure the formatter as follows:
- Add the field to an entity/node;
- Select the 'Office hours' formatter;
- Set the formatter details at
  `/admin/structure/types/manage/NODE_TYPE/display/VIEW_MODE;`
or
- Add the field to a view;
- Select the 'Office hours' formatter;
- Check the formatter settings of the field;

## FORMATTING THE HOURS 

Using the customizable separators in the formatter settings, you can format
the hours any way you want.
- The formatter is default set up to show a nice table.
- To export the data to a Google Places bulk upload file, you can create a view
  and set the formatter to generate the following data
  (for a shop that opens from Monday to Friday):
     2:10:00:18:00,3:10:00:18:00,4:10:00:18:00,
     5:10:00:18:00,6:10:00:18:00,7:12:00:20:00

## FORMATTING THE HOURS - ALTER HOOKS 

Alter_hooks are introduced and documented in office_hours.api.php:
 - ` hook_office_hours_time_format_alter(string &$formatted_time)` (v8.x-1.7)
   allowing to change the time format, and/or insert a translatable text,
   in order to change the formatted hours to your organization's needs.
 - `hook_office_hours_current_time_alter(int &$time, $entity)` (v8.x-1.7)
   allowing to change the 'current' (user) time,
   in order to change the isOpen indicator (and Current day formatter).

## USING VIEWS - FIELDS 

Add the Field to any Views display, as you are used to do.
- To show only 1 day per row in a Views display: 
  - add the field to your View,
  - open the `MULTIPLE FIELD SETTINGS` section,
  - uncheck the option 'Display all values in the same row',
  - make also sure you display 'all' values.
     (only valid if you have upgraded from 1.1 version.)

## USING VIEWS - FILTER CRITERIA

Only default (out-of-the-box) Views functionality is provided.
- To show only the entities that have a office hours: 
  - add the filter criterion 'Content: Office hours (field_office_hours:day)',
  - set the filter option 'Operator' to 'is not empty',
- To show only the entities that have office hours for e.g., Friday: 
  - add the filter criterion 'Content: Office hours (field_office_hours:day)',
  - set the filter option 'Operator' to 'is equal to',
  - set the filter option 'Value' to '5',
     or leave 'Value' empty and set 'Expose operator' to YES.
- To show only the entities that are open NOW: This is not possible, yet.

## USING VIEWS - SORT CRITERIA 

Only default (out-of-the-box) Views functionality is provided.
- To sort the time slots per day, add the 'day' sort criterion.

## USING VIEWS - CREATE A BLOCK PER NODE/entity

Suppose you want to show the Office hours on a node page,
but NOT on the page itself,
but rather in a separate block, follow these instructions:
(If you use non-Node entities, you'll need to adapt some settings.)
1. First, create a new View for 'Content', and add a Block display;
 - Under FORMAT, set to an unformatted list of Fields;
 - Under FIELDS, add the office_hours field and other fields you like;
 - Under FILTER CRITERIA, add the relevant Content type(s);
 - Under PAGER, show all items;
 - Now open the ADVANCED section;
 - Under CONTEXTUAL FILTERS, add 'Content: Nid';
 -- Set 'Provide default value' to 'Content ID from URL';
 -- Set 'Specify validation criteria' to
     the same Content type(s) as under FILTERS;
 -- Set 'Filter value format' according to your wishes;
 -- Set 'Action to take if filter value does not validate' to 'Hide View';
 - Tweak the other settings as you like.

2. Now, configure your new Block under `/admin/structure/block/manage/` : 
 - Set the Block title, and the Region settings;
 - Under PAGES, set `'Show block on specific pages'`
   to `'Only the listed pages'` and `'node/*';`
   You might want to add more pages, if you use other non-node entity types.
 - Tweak the other settings as you like.
 You'll need to tune the block for the following cases: 
 - A user accesses the node page, but 'Access denied';
 - A node is unpublished;

  Now, test your node page. You'll see the Office hours in the page
  AND in the block. That's once too often.

3. So, modify the 'View mode' of your Content type under
  ` /admin/structure/types/manage/<MY_CONTENT_TYPE>/display`
 - Select`MANAGE DISPLAY;`
 - Select or create a View mode.
 - Select the `Office_hours,` and set the Format to `'Hidden'`;
 - Save the data, end enjoy the result!

## D7: IMPORTING WITH FEEDS MODULE 

To import data with the Feeds module, the following columns can be used:
- day;
- hours/morehours from;
- hours/morehours to;
- hours/morehours from-to.

The day should be stated in full English name, or a day number
where Sunday=0, Monday=1, etc.
The hours can be formatted as 'hh:mm' or 'hh.mm'.

Probably Feeds Tamper can help to format the data to the proper format.

Here is an example file:
nid;weekday;Hours_1;Hours_2
2345;Monday;11:00 - 18:01;
2345;Tuesday;10:00 - 12:00;13:15-17.45
2383;Monday;11:00 - 18:01;
2383;Tuesday;10:00 - 12:00;13:15-17.45

## D8: Migrating from external source 
Create a migrate process plugin that returns an array like this:

```
  [
    [1] => [
      [day] => 1
      [starthours] => 1600
      [endhours] => 2200
    ]
    [2] => [
      [day] => 2
      [starthours] => 1600
      [endhours] => 2200
    ]
    [3] => [
      [day] => 3
      [starthours] => 1600
      [endhours] => 2200
    ]
    [4] => [
      [day] => 4
      [starthours] => 1300
      [endhours] => 2200
    ]
    [5] => [
      [day] => 5
      [starthours] => 1300
      [endhours] => 2300
    ]
    [6] => [
      [day] => 6
      [starthours] => 1300
      [endhours] => 2300
    ]
    [7] => [
      [day] => 0
      [starthours] => 1300
      [endhours] => 2100
    ]
  ]
```
Note that the array key doesn't matter, but that day 0 = Sunday.
If you have multiple slots per day,
just add a new entry with the same the [day] value.

In your migration yml, you can do something like this: 

  field_opening_hours:
  ```
    -
      plugin: opening_hours
      source: opening_hours
    -
      plugin: office_hours_field_plugin
  ```

where the office_hours_field_plugin is supplied by this office_hours module.

## MAINTAINERS

- Ozeuss - https://www.drupal.org/u/ozeuss
- John Voskuilen (johnv) - https://www.drupal.org/u/johnv
- Mikkel HÃ¸gh (mikl) - https://www.drupal.org/u/mikl
- Dave Hall (skwashd) - https://www.drupal.org/u/skwashd
