'use strict';

/**
 * @todo check js file for new API.
 * @see https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview
 */
(function updateElement($, Drupal) {
  Drupal.behaviors.office_hours = {
    attach: function doUpdateElement(context, settings) {

      /**
       * Traverses visible rows and applies even/odd classes.
       */
      function fixStriping() {
        $('tbody tr:visible', context).each(function setStriping(i) {
          if (i % 2 === 0) {
            $(this).removeClass('odd').addClass('even');
          } else {
            $(this).removeClass('even').addClass('odd');
          }
        });
      }

      /**
       * Fills a slot item with the new value,
       * and shows the next item slowly if needed.
       *
       * @param formItem The time slot.
       * @param value The new value.
       */
      function setTimeslot(formItem, value) {
        formItem.val(value);
        if (value) {
          // Show the next item, slowly.
          formItem.closest('tr').fadeIn('slow');
        }
      }

      /**
       * Shows the Add-link, conditionally.
       */
      function showAddLink() {
        var nextTr;

        nextTr = $(this).closest('tr').next();

        $(this).hide();
        if (nextTr.is(':hidden')) {
          $(this).show();
        }
      }

      /**
       * Shows an office-hours-slot, when user clicks "Add more".
       * @param e
       */
      function showTimeslot(e) {
        var $nextTr;

        e.preventDefault();

        // Hide the link, the user clicked upon.
        $(this).hide();

        // Show the next slot item, slowly.
        $nextTr = $(this).closest('tr').next();
        $nextTr.fadeIn('slow');

        fixStriping();
      }

      /**
       * Enable/Disable the time input elements, depending on the all-day checkbox.
       *
       * @todo #3322982 When 'all_day' is set, the link 'Add time slot' must be hidden.
       */
      function setAllDayTimeslot() {

        // Get the name of the checkbox, which will be mostly the
        // same name for the start and end times.
        var name = $(this).attr('name');

        // Determine the state of the all_day checkbox.
        var isEnabled = $(this).is(':checked');

        // Variable to store all the names of the start/end times.
        var timeNames = [];

        // Replace [all_day] with the names for start and end times.
        // For HTML5 element.
        timeNames.push(name.replace('[all_day]', '[starthours][time]'));
        timeNames.push(name.replace('[all_day]', '[endhours][time]'));
        // For select list element.
        timeNames.push(name.replace('[all_day]', '[starthours][hour]'));
        timeNames.push(name.replace('[all_day]', '[starthours][minute]'));
        timeNames.push(name.replace('[all_day]', '[starthours][ampm]'));
        timeNames.push(name.replace('[all_day]', '[endhours][hour]'));
        timeNames.push(name.replace('[all_day]', '[endhours][minute]'));
        timeNames.push(name.replace('[all_day]', '[endhours][ampm]'));

        // Enable/Disable the start and end time depending on all_day checkbox.
        timeNames.forEach(function (item) {
          $('[name="' + item + '"]').prop("disabled", isEnabled);
        });
      }

      // Hide every item above the max slots per day.
      $('.office-hours-hide').hide();

      // When the document loads, look for checked 'all day'
      // checkboxes, and disable the start and end times.
      $(document).ready(function () {
        // Loop through all the all day checkboxes that are checked.
        $('[id*="all-day"]:checked').each(setAllDayTimeslot);
      });

      // Attach a function to each all_day checkbox, to enable/disable the times
      // when the checkbox is clicked.
      $('[id*="all-day"]').bind('click', setAllDayTimeslot);

      // Attach a function to each Add-link to show the next slot if clicked upon.
      // Show the Add-link, except if the next time slot is hidden.
      $('.office-hours-add-link').bind('click', showTimeslot)
        .each(showAddLink);

      fixStriping();

      // Clear the content of this timeslot, when user clicks "Clear/Remove".
      // Do this for both widgets.
      $('.office-hours-delete-link').bind('click', function deleteLink(e) {

        // Clear the value from the element.
        function clearValue() {
          $(this).val($('#target').find('option:first').val());
        }

        e.preventDefault();
        // Clear the date (in Exception days).
        $(this).parent().parent().find('.form-date').each(clearValue);
        // Clear the all_day checkbox.
        $(this).parent().parent().find('.form-checkbox').each(function deleteAllDayCheckbox() {
          if ($(this).is(':checked')) {
            // Trigger setAllDayTimeslot().
            $(this).click();
          }
        });
        // Clear the hours, minutes in the select box.
        $(this).parent().parent().find('.form-select').each(clearValue);
        // Clear the hours, minutes in the HTML5 time element.
        $(this).parent().parent().find('.form-time').each(clearValue);
        // Clear the comment.
        $(this).parent().parent().find('.form-text').each(clearValue);

        // Hide the link.
        $(this).hide();
      });

      // Copy values from previous day, when user clicks "Copy previous day".
      // @todo This works for Table widget, not yet for List Widget.
      $('.office-hours-copy-link').bind('click', function copyPreviousDay(e) {
        var currentDay;
        var currentSelector;
        var previousDay;
        var previousSelector;
        var tbody;

        e.preventDefault();

        // Get current day using attribute, both for Week Widget and List Widget.
        // @todo Use only attribute, not both attribute and class name.
        currentDay = parseInt($(this).closest('tr').attr('office_hours_day'));
        if(Number.isNaN(currentDay)) {
          // Basic List Widget.
          currentDay = parseInt($(this).closest('fieldset').attr('office_hours_day'));
        }
        if(Number.isNaN(currentDay)) {
            // Error.
        } else {
          // Week widget can have value 0 (sunday). List widget starts with value 1.
          previousDay = (currentDay == 0) ? currentDay + 6 : currentDay - 1;

          // Select current table.
          tbody = $(this).closest('tbody');
          // Get div's from current day using class name.
          currentSelector = tbody.find('.office-hours-day-' + currentDay);
          // Get div's from previous day using class name.
          previousSelector = tbody.find('.office-hours-day-' + previousDay);
        }

        // For better UX, first copy the comments, then hours and fadeIn.
        // Copy the comment.
        previousSelector.find('.form-text').each(function copyComment(index) {
          setTimeslot(currentSelector.find('.form-text').eq(index), $(this).val());
        });
        // do NOT copy the day/date in the select list/HTML5 date element (List widget).
        previousSelector.find('.form-date').each(function copyDateInHtml5(index) {
          // setTimeslot(currentSelector.find('.form-date').eq(index), $(this).val());
        });
        previousSelector.find('.form-checkbox').each(function copyAllDay(index) {
          // Determine the state of the all_day checkbox.
          var previousIsEnabled = $(this).is(':checked');
          var currentIsEnabled = $(currentSelector.find('.form-checkbox').eq(index)).is(':checked');
          if (previousIsEnabled !== currentIsEnabled) {
            // Trigger setAllDayTimeslot().
            $(currentSelector.find('.form-checkbox').eq(index)).click();
          }
        });
        // Copy the hours, minutes in the select box.
        previousSelector.find('.form-select').each(function copyTimeInSelect(index) {
          setTimeslot(currentSelector.find('.form-select').eq(index), $(this).val());
        });
        // Copy the hours, minutes in the select list/HTML5 time element.
        previousSelector.find('.form-time').each(function copyTimeInHtml5(index) {
          setTimeslot(currentSelector.find('.form-time').eq(index), $(this).val());
        });

        // If needed, show each Add-link of the day, after "Copy previous day".
        currentSelector.find('.office-hours-add-link').each(showAddLink);
        // @todo If needed, show each Remove/Delete-link of the day.
        currentSelector.find('.office-hours-delete-link').each(showAddLink);
      });
    }
  };
})(jQuery, Drupal);
