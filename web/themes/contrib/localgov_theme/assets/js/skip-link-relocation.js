"use strict";

/**
 * @file
 * Move the skip-link to the top.
 *
 * Currently, the EU cookie compliance popup forces itself as the first child
 * of the body tag.  But we want the "Skip to main content" link to remain as
 * the first child.  Here we relocate the skip-link so that it remains the first
 * child of the body element.  This helps screen reader users.
 *
 * @see localgov_theme/templates/system/html.html.twig
 * @see Drupal.eu_cookie_compliance.createWithdrawBanner()
 */

(function moveSkipLinkToTop(Drupal) {
  Drupal.behaviors.moveSkipLinkToTop = {
    attach: function attach() {
      var skipLink = document.querySelector("body > a.skip-link");
      var hasNoSkipLink = !skipLink;
      if (hasNoSkipLink) {
        return;
      }

      var firstItem = document.querySelector("body > :first-child");
      var isSkipLinkAtTheTop = firstItem === skipLink;
      if (isSkipLinkAtTheTop) {
        return;
      }

      // Move skip-link above the popup (or whatever is sitting there).
      firstItem.parentNode.insertBefore(skipLink, firstItem);
    }
  };
})(Drupal);
