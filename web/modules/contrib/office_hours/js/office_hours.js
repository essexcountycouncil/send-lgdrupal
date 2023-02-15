(function ($) {
  'use strict';

  Drupal.behaviors.office_hours = {
    attach: function (context, settings) {

      // Hide every item above the max slots per day.
      $('.office-hours-hide').hide();

      // Attach a function to each Add-link to show the next slot if clicked upon.
      // Show the Add-link, except if the next time slot is hidden.
      $('.office-hours-add-link')
        .bind('click', show_time_slot)
        .each(show_add_link);

      fix_striping();

      // Clear the content of this a, when user clicks "Clear/Remove".
      // Do this for both widgets.
      $('.office-hours-delete-link').bind('click', function (e) {
        e.preventDefault();
        // Clear the date (in Exceptions Date).
        $(this).parent().parent().find('.form-date').each(function () {
          $(this).val($('#target').find('option:first').val());
        });
        // Clear the hours, minutes in the select box.
        $(this).parent().parent().find('.form-select').each(function () {
          $(this).val($('#target').find('option:first').val());
        });
        // Clear the hours, minutes in the HTML5 time element.
        $(this).parent().parent().find('.form-time').each(function () {
          $(this).val($('#target').find('option:first').val());
        });
        // Clear the comment.
        $(this).parent().parent().find('.form-text').each(function () {
          $(this).val($('#target').find('option:first').val());
        });
        // Hide the link.
        $(this).hide();
      });

      // Copy values from previous day, when user clicks "Copy previous day".
      // @todo This works for Table widget, not yet for List Widget.
      $('.office-hours-copy-link').bind('click', function (e) {
        e.preventDefault();

        // Read current day; presume Week Widget, then check if List Widget is used.
        var current_day = parseInt($(this).closest('tr').find('input')[0].value);
        if(isNaN(current_day)) {
          // List widget.
          current_day = parseInt($(this).closest('div div').find('select')[0].value);
          // Div's from current day.
          var current_selector = $(this).closest('tr');
          // Div's from previous day.
          var previous_selector = current_selector.prev().hasClass('office-hours-hide') ? current_selector.prev().prev() : current_selector.prev();
        } else {
          // Week widget.
          var previous_day = (current_day == 0) ? current_day + 6 : current_day - 1;

          // Select current table.
          var tbody = $(this).closest('tbody');
          // Div's from previous day.
          var previous_selector = tbody.find('.office-hours-day-' + previous_day);
          // Div's from current day.
          var current_selector = tbody.find('.office-hours-day-' + current_day);
        }

        // For better UX, first copy the comments, then hours and fadeIn.
        // Copy the comment.
        previous_selector.find('.form-text').each(function (index) {
          set_time_slot_value(current_selector.find('.form-text').eq(index), $(this).val());
        });
        // Copy the hours, minutes in the select box.
        previous_selector.find('.form-select').each(function (index) {
          set_time_slot_value(current_selector.find('.form-select').eq(index), $(this).val());
//          if (this.id.includes('hours')) {
//            set_time_slot_value(current_selector.find('.form-select').eq(index), $(this).val());
//          }
        });
        // Copy the hours, minutes in the select list/HTML5 time element.
        previous_selector.find('.form-time').each(function (index) {
          set_time_slot_value(current_selector.find('.form-time').eq(index), $(this).val());
        });
        // Copy the day/date in the select list/HTML5 date element (List widget).
        previous_selector.find('.form-date').each(function (index) {
          set_time_slot_value(current_selector.find('.form-date').eq(index), $(this).val());
        });

        // If needed, show each Add-link of the day, after "Copy previous day".
        current_selector.find('.office-hours-add-link').each(show_add_link);
        // @todo If needed, show each Remove/Delete-link of the day.
        current_selector.find('.office-hours-delete-link').each(show_add_link);

        /**
         * Fills a slot item with the new value,
         * and shows the next item slowly if needed.
         *
         * @param form_item
         *   The time slot.
         * @param value
         *   The new value.
         */
        function set_time_slot_value(form_item, value) {
          form_item.val(value);
          if (value) {
            // Show the next item, slowly.
            form_item.closest('tr').fadeIn('slow');
          }
        }

      });

      /**
       * Shows an office-hours-slot, when user clicks "Add more".
       * @param e
       * @returns {boolean}
       */
      function show_time_slot(e) {
        e.preventDefault();
        // Hide the link, the user clicked upon.
        $(this).hide();
        // Show the next slot item, slowly.
        var $next_tr = $(this).closest('tr').next();
        $next_tr.fadeIn('slow');

        fix_striping();
        return false;
      }

      /**
       * Shows the Add-link, conditionally.
       */
      function show_add_link() {
        var next_tr = $(this).closest('tr').next();
        $(this).hide();
        if (next_tr.is(':hidden')) {
          $(this).show();
        }
      }

      // Function to traverse visible rows and apply even/odd classes.
      function fix_striping() {
        $('tbody tr:visible', context).each(function (i) {
          if (i % 2 === 0) {
            $(this).removeClass('odd').addClass('even');
          }
          else {
            $(this).removeClass('even').addClass('odd');
          }
        });
      }
    }
  };
})(jQuery);
