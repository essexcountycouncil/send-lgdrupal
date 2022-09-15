/**
 * @file JS file for the Header component.
 */

// Small polyfill needed for IE11
// We can remove this when we stop supporting IE11.
if (window.NodeList && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = Array.prototype.forEach;
}

(function lgdHeaderScript(Drupal) {
  Drupal.behaviors.lgdHeader = {
    attach: function (context) {
      context = context || document;

      const headerSearchForm = document.querySelector('.lgd-region--search form');
      const textInput = headerSearchForm.querySelector('.form-text');
      textInput.placeholder = 'Search our website';

    },
  };
})(Drupal);
