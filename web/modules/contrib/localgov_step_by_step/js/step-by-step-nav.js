/**
 * @file
 * Additional behaviour for the Step by step navigation.
 */

(function($, Drupal, drupalSettings) {

  const stepByStep = {};
  stepByStep.showAllText = 'Show summaries';
  stepByStep.hideAllText = 'Hide summaries';
  stepByStep.showStepText = 'Show step summary';
  stepByStep.hideStepText = 'Hide step summary';

  // Set visibility based on specified button.step-show elements.
  function summaryVisiblity(elements, cmd) {
    switch(cmd) {
      case 'show':
        elements.each(function() {
          var stepTitle = $(this).parents('.step__title').find('a').text();
          $(this).parents('.step').find('.step__summary').addClass('step-show-summary');
          $(this).text(stepByStep.hideStepText);
          $(this).attr("aria-expanded", "true");
          $(this).attr('aria-label', "Hide " + stepTitle + " summary");
        });
        // 'Hide all' control displayed if all steps are shown.
        if ($('.step__summary').length === $('.step-show-summary').length) {
          $('.step-master').text(stepByStep.hideAllText);
          $('.summaries-control i').addClass('fa-eye-slash').removeClass('fa-eye');
        }
        break;

      case 'hide':
        elements.each(function() {
          var stepTitle = $(this).parents('.step__title').find('a').text();
          $(this).parents('.step').find('.step__summary').removeClass('step-show-summary');
          $(this).attr("aria-expanded", "false");
          $(this).text(stepByStep.showStepText);
          $(this).attr('aria-label', "Show " + stepTitle + " summary");
        });
        // 'Show all' control displayed if any steps are hidden.
        $('.step-master').text(stepByStep.showAllText);
        $('.summaries-control i').addClass('fa-eye').removeClass('fa-eye-slash');
        break;
    }
  }

  // Insert show all button.
  $("<div class='summaries-control'><i class='fas fa-eye'></i><button aria-expanded='false' class='step-master ml-2'>" + stepByStep.showAllText + "</button></div>").insertBefore("ol.step-list");

  // Insert hide/show button for each step.
  function stepSummaryButton(isVisible, stepTitle) {
    var $container = $("<span class='step-summary-container'>");
    var $button = $("<button class='step-show'>");
    $button.attr('aria-expanded', isVisible ? "true" : "false");
    $button.attr('aria-label', (isVisible ? "Hide " : "Show ") + stepTitle + " summary");
    $button.text(isVisible ? stepByStep.hideStepText : stepByStep.showStepText);
    $container.append($button);
    return $container;
  }

  $("ol.step-list .step").each(function() {
    var isVisible = $(this).hasClass('step--active');
    var stepTitle = $(this).find('.step__title').text();
    if (isVisible) {
      $(this).find('.step__summary').addClass('step-show-summary');
    }
    $(this).find('.step__title').append(stepSummaryButton(isVisible, stepTitle));
  });

  // Show / hide all.
  $('.step-master').on("click", function () {
    $('.summaries-control i').toggleClass('fa-eye fa-eye-slash');
    if ($(this).text() === stepByStep.showAllText) {
      $(this).text(stepByStep.hideAllText).attr('aria-expanded', true);
      summaryVisiblity($('.step-show'), 'show');
    } else {
      $(this).text(stepByStep.showAllText).attr('aria-expanded', false);
      summaryVisiblity($('.step-show'), 'hide');
    }
  });

  // Show / hide single step.
  $('.step-show').on("click", function () {
    $(this).parents('.step').find('.step__summary').toggleClass('step-show-summary');
    if ($(this).text() === stepByStep.showStepText) {
      summaryVisiblity($(this), 'show');
    } else {
      summaryVisiblity($(this), 'hide');
    }
  });

})(jQuery, Drupal, drupalSettings);
