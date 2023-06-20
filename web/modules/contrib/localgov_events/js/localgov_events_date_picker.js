/**
 * @file
 * Events date picker functionality.
 */

(function ($) {
  Drupal.behaviors.localgovEventsDatePicker = {
    attach: function attach(context, settings) {

      const firstDayOfWeek = function(date) {
        return new Date(date.setDate(date.getDate() - date.getDay() + (date.getDay() === 0 ? -6:1) ));
      }

      const lastDayOfWeek = function(date) {
        return new Date(date.setDate(date.getDate() - date.getDay() +7));
      }

      $('.js-date-picker').on('change', function() {

        let start_date = null;
        let end_date = null;
        const today = new Date();

        switch(this.value) {

          case 'today':
            start_date = today;
            end_date = today;
            break;

          case 'tomorrow':
            const tomorrow = today.setDate(today.getDate() + 1);
            start_date = new Date(tomorrow);
            end_date = new Date(tomorrow);
            break;

          case 'this_week':
            start_date = firstDayOfWeek(today)
            end_date = lastDayOfWeek(today);
            break;

          case 'this_month':
            start_date = new Date(today.getFullYear(), today.getMonth() , 1, 1);
            end_date = new Date(today.getFullYear(), today.getMonth() + 1, 0, 1);
            break;

          case 'next_month':
            if (today.getMonth() < 11) {
              start_date = new Date(today.getFullYear(), today.getMonth() + 1, 1, 1);
              end_date = new Date(today.getFullYear(), today.getMonth() + 2, 0, 1);
            }
            else {
              start_date = new Date(today.getFullYear() + 1, 0, 1, 1);
              end_date = new Date(today.getFullYear() + 1, 1, 0, 1);
            }
            break;

        }

        if (start_date) {
          $('.js-date-picker-start').val(start_date.toISOString().substr(0, 10));
        }
        else {
          $('.js-date-picker-start').val('');
        }
        if (end_date) {
          $('.js-date-picker-end').val(end_date.toISOString().substr(0, 10));
        }
        else {
          $('.js-date-picker-end').val('');
        }
      });

    }
  }
})(jQuery);
