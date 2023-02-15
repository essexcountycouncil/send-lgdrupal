/**
 * @file
 *
 * UI on the form itself
 *
 */

(function ($, Drupal) {
  Drupal.behaviors.hideShowLogs = {
    attach: function (context, settings) {
      'use strict';

      Drupal.webform = Drupal.webform || {};

      // If transition changes, show log fields, otherwise hide
      $('select.workflow-transition.form-select, fieldset.workflow-transition.form-composite').on('change', function () {
        var val = $(this).find(":selected, :checked").length > 0 ? $(this).find(":selected, :checked").val() : '';
        var logs = $('.form-item--workflow-log-public, .form-item--workflow-log-admin, .form-item-workflow-log-public, .form-item-workflow-log-admin');
        if (!val || val == '') {
          logs.slideUp();
          logs.find('textarea').val('');
        } else {
          logs.slideDown();
        }
      });

      $(document).ready(function () {
        $('.workflow-transition.form-select, .workflow-transition.form-composite').trigger('change');
      });

    }
  };
}(jQuery, Drupal));
