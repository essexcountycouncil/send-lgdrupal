/**
 * @file
 * Additional behaviors for the Service status view.
 *
 * - Jump to the appropriate Tab or Accordion content based on the URL fragment.
 *   This allows us to share and bookmark a particular status message.
 *
 * Syntax: ES5
 */

/* eslint no-var: "off", prefer-template: "off", no-restricted-globals: "off" */
(function jumpToStatusMsg(Drupal) {
  /**
   * Which status message are we after?
   *
   * The URL fragment will either be #status-N or #status-mobile-N.  From this, we need
   * to extract the N.
   *
   * @return {mixed}
   *   Integer or bool.
   */
  function findTargetStatusMessageNumber() {
    var isNum = false;
    var statusNumber = "";

    if (!location.hash) {
      return false;
    }

    statusNumber = location.hash.replace(/#status-mobile-|#status-/, "");

    isNum = /\d+/.test(statusNumber);
    if (isNum) {
      return parseInt(statusNumber, 10);
    }

    return false;
  }

  Drupal.behaviors.jumpToStatusMessage = {
    /**
     * Display tab/accordion content based on URL fragment.
     *
     * When the page with the Service status list has a URL fragment, we use
     * that to jump to the corresponding Service status message up on page load.
     * This is how HTML behaves anyway; we are simply bringing this to our
     * Tab/Accordion hybrid.
     *
     * @param {object} context
     *   Usually, the DOM element.
     * @param {object} settings
     *   Reference to window.drupalSettings.
     */
    attach(context) {
      var statusNum = findTargetStatusMessageNumber();

      var tabSelector = 'a[href="#status-' + statusNum + '"]';
      var accordionSelector = "#heading-" + statusNum + " a";

      var isTabVisible = document.getElementById("tabs").offsetParent;

      if (isTabVisible) {
        jQuery(tabSelector, context).tab("show");
      } else {
        // The Bootstrap collapse() method is giving inconsistent results in
        // some cases.  So directly clicking the Accordion header.
        jQuery(accordionSelector, context).click();
      }
    }
  };
})(Drupal);
