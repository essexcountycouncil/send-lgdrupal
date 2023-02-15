/**
 * @file
 *
 * UI on the element editing form
 *
 */

(function ($, Drupal) {
  Drupal.behaviors.hideShowAccess = {
    attach: function (context, settings) {
      'use strict';

      Drupal.webform = Drupal.webform || {};

      function updateAccessFromCheckbox(checkbox) {
        console.log('updateAccessFromCheckbox', checkbox);
        var wrapper = checkbox.closest('.details-wrapper');
        if (checkbox.is(':checked')) {
          wrapper.children().show();
        } else {
          wrapper.children().hide();
        }
        checkbox.parent().show();
        wrapper.find('[id$="override"]').parent().show();
      }

      $('[id$="workflow-enabled"]').on('change', function () {
        updateAccessFromCheckbox($(this));
      });
      $('[id$="override"]').on('change', function () {
        updateAccessFromCheckbox($(this));
      });

      $('[id$="workflow-enabled"]').trigger('change');
      $('[id$="override"]').trigger('change');

    }
  };
}(jQuery, Drupal));
