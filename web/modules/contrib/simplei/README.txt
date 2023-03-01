SIMPLEI
-------
Simple Environment Indicator


DESCRIPTION
-------------------
* Puts envriotnment indicator in color to the Toolbar.
* As of version 2.x, it supports Gin admin theme.


USAGE
-----
1. Enable the module like any other. The module works with Drupal Toolbar and
   Gin admin theme.

2. Enter a line like the examples in your settings.local.php file.

   The web color name(s) or hex value(s) is followed by environment name.
   You can specify background color only (foreground is white) or foreground/background colors.

   - Web color names.
   $settings['simple_environment_indicator'] = 'DodgerBlue Local';
   $settings['simple_environment_indicator'] = 'Black/Cyan Local';

   - Hex value colors.
   $settings['simple_environment_indicator'] = '#1E90FF DEV';
   $settings['simple_environment_indicator'] = '#33333/#DDBB00 DEV';

   Predefined color for known environment names.
   $settings['simple_environment_indicator'] = '@production';

   Environment name following @ sign will have predetermined background color.
   Recognized environment names are:
   - production, prod, prd, live (matches first two chars, pr & li)
   - staging, stage, stg, test (matches first two chars, st & te)
   - development (or any string that does not match above)

3. The indicator for logged in users appears only when Toolbar is enabled.

4. To support anonymous users, add another line in settings.local.php,

   $settings['simple_environment_anonymous'] = TRUE;

   If you do not like default rendering of the environment indicator, you can
   set to string instead of boolean value, such as,

   $settings['simple_environment_anonymous'] = "body:after { 
     content: \"STAGE\" ;
     position: fixed;
     top: 0;
     left: 0;
     padding: 0.1em 0.5em;
     font-family: monospace;
     font-weight: bold;
     color: #fff;
     background: brown;
     border: 1px solid white; }";

   You would not want to display indicator for anonymous users in production
   environment, but nothing will stop you if you have a reason to do so.
