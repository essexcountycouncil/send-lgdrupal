/**
 * @file
 * Attaches several event listener to a web page.
 */

(function ($, drupalSettings) {

  'use strict';

  $(document).ready(function () {
    defaultBind();

    // Colorbox: This event triggers when the transition has completed and the
    // newly loaded content has been revealed.
    if (drupalSettings.matomo && drupalSettings.matomo.trackColorbox) {
      $(document).bind('cbox_complete', function () {
        var href = $.colorbox.element().attr('href');
        if (href) {
          _paq.push(['setCustomUrl', href]);
          if (drupalSettings.matomo.disableCookies) {
            _paq.push(['disableCookies']);
          }
          _paq.push(['trackPageView']);
        }
      });
    }

  });

  /**
   * Default event binding.
   *
   * Attach mousedown, keyup, touchstart events to document only and catch
   * clicks on all elements.
   */
  function defaultBind() {
    $(document.body).bind('mousedown keyup touchstart', function (event) {

      // Catch the closest surrounding link of a clicked element.
      $(event.target).closest('a,area').each(function () {

        if (drupalSettings.matomo.trackMailto && $(this).is("a[href^='mailto:'],area[href^='mailto:']")) {
          // Mailto link clicked.
          _paq.push(['trackEvent', 'Mails', 'Click', this.href.substring(7)]);
        }

      });
    });
  }

})(jQuery, drupalSettings);
