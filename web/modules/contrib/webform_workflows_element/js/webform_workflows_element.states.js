/**
 * @file
 * JavaScript behaviors for custom webform #states for webform_workflows_element
 */

(function ($, Drupal) {

  'use strict';

  Drupal.webform = Drupal.webform || {};
  Drupal.webform.states = Drupal.webform.states || {};
  var $document = $(document);

  // Disable any option in the webform 'transition' subelement
  // That corresponds to the selected disabled transition
  $('.form-select.workflow-transition option').each(function () {
    const option = this;
    var elementId = 'workflow'; // @todo
    var transitionId = option.value;
    var stateEvent = 'state:disable_transition-' + transitionId;
    var messageId = 'edit-' + elementId + '-transition-disabled-message-' + transitionId;
    $('#' + messageId).addClass('element-invisible');

    // @todo incorporate element ID to allow for multiple workflow elements : elementId + '-'
    $document.on(stateEvent, function (e) {
      if (e.trigger && $(e.target).isWebformElement() && $.contains(e.target, option)) {
        console.log(e);
        if (e.value) {
          $(option).attr('disabled', 'disabled');

          // @todo low priority - get trigger information so the user knows what did it
          // $('#' + messageId + ' .messages span.details').remove();
          // $('#' + messageId + ' .messages').append('<span class="details">&nbsp;(trigger: NOTE)</span>');

          $('#' + messageId).removeClass('element-invisible');
        } else {
          $(option).attr('disabled', false);
          $('#' + messageId).addClass('element-invisible');
        }
      }
    });
  });

})(jQuery, Drupal);
