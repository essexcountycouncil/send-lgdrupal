/**
 * @file
 *
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var settings = drupalSettings.webform_workflows_element;

  var colors = settings ? settings.colors : false;
  var allColorClasses = colors ? Object.values(colors).concat(['clear-webform-color']) : false;

  var updateElementClass = function (element, value) {

    element.removeClass(allColorClasses.join(' '));
    if (!value) {
      element.addClass('clear-webform-color');
    }
    else {
      var className = colors ? colors[value] : false;
      if (className) {
        element.addClass(className);
      }
      else {
        element.addClass('clear-webform-color');
      }
    }
  };

  var updateStylesChosen = function (chosen) {
    console.log('updateStylesChosen', chosen);
    chosen.find('.chosen-drop li>span, .chosen-choices li>span, .chosen-single, li.active-result, li.result-selected').each(function () {
      var value = $(this).text().trim();
      updateElementClass($(this), value);
    });
  };

  var updateStylesSelect = function (select) {
    select.removeClass(allColorClasses.join(' '));
    var value = select.val();
    updateElementClass(select, value);

    // Options
    select.find('option').each(function () {
      var value = $(this).text().trim();
      updateElementClass(select, value);
    });
  };

  $(document).ready(function () {
    // Normal selects:
    $('select.webform_workflows_element_filter_states').each(function () {
      var select = $(this);
      updateStylesSelect(select);
      select.on('click', function () {
        updateStylesSelect(select);
      });
      select.on('change', function () {
        updateStylesSelect(select);
      });
    });

    // Chosen:
    var chosen = $('.webform_workflows_element_filter_states.chosen-container');
    if (chosen) {
      updateStylesChosen(chosen);
      chosen.on('click', function () {
        updateStylesChosen(chosen);
      });
      chosen.on('change', function () {
        updateStylesChosen(chosen);
      });
    }
  });

})(jQuery, Drupal, drupalSettings);
