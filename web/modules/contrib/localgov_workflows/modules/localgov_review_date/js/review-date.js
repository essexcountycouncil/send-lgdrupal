/**
 * @file
 * Defines JavaScript behaviors for the review date widget.
 */
(function ($, Drupal, drupalSettings) {

  /**
   * Show review date summary on node edit form.
   */
  Drupal.behaviors.ReviewDateSummary = {
    attach: function attach(context) {
      const $context = $(context);
      $context.find('.review-date-form').drupalSetSummary(function (context) {
        const lastReview = $('.review-date-last-review').val();
        const nextReview = $('.review-date-next-review').val();

        if (lastReview && nextReview) {
          return Drupal.t('Last reviewed on @last<br>Next review on @next', {
              '@last': lastReview,
              '@next': nextReview,
            }
          );
        }

        return Drupal.t('Not reviewed yet');
      });
    }
  };

  /**
   * Update review date when next review date select changes.
   */
  Drupal.behaviors.ReviewDateNextReviewSelect = {
    attach: function attach(context) {
      $('.review-date-review-in').change(function() {
        const reviewIn = parseInt($('.review-date-review-in').val());
        let today = new Date();
        const reviewDate = new Date(today.setMonth(today.getMonth() + reviewIn));

        $('.review-date-review-date').val(reviewDate.toISOString().slice(0, 10));
      });
    }
  };

  /**
   * Set content reviewed if content moderation state set to published.
   */
  Drupal.behaviors.ReviewDateSetReviewed = {
    attach: function attach(context) {
      $('#edit-moderation-state-0-state').change(function() {
        const moderation_state = $('#edit-moderation-state-0-state').val();
        if (moderation_state === 'published') {
          const reviewed = $('.review-date-reviewed');
          reviewed.prop('checked', true);
          reviewed.trigger('change');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
