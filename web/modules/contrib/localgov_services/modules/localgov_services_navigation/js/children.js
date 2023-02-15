/**
 * @file
 *   Drag and Drop addition to Landing forms.
 *
 * @see \Drupal\localgov_services_navigation\EntityChildRelationshipUi
 *
 * Drag from the listed children that are not referenced by the landing page
 * yet, to entity reference and link fields, to auto populate them.
 *
 * Manually tested with Claro and Stark.
 *
 * Uses basic HTML Drag and Drop so will work on most desktop, but not mobile.
 * For most draggable core uses deprecated jquery_ui draggable and new
 * SortableJS. SortableJS is for moving in and between lists. This changes
 * content for different field types, so for more compatibility maybe an
 * additional library would be required.
 */
(function ($, Drupal) {

  var dragChild;

  Drupal.behaviors.localgovServiceChild = {
    attach: function attach(context, settings) {
      // Add draggability to child items.
      var child = $('.localgov-child-drag');
      child.each(function() {
        this.setAttribute('draggable', true);
        this.classList.add('draggable');
        this.addEventListener('dragstart', function (event) {
          event.dataTransfer.dropEffect = "move";
          event.dataTransfer.effectAllowed = "move";
          dragChild = this;
        });
      });
    }
  };

  Drupal.behaviors.localgovServiceTaskDrop = {
    attach: function attach(context, settings) {
      // Is it always a table. Maybe form-item and then parent?
      var linkRow = $("[data-drupal-selector='edit-localgov-common-tasks'] tr");
      linkRow.each(function() {
        this.addEventListener('dragover', function (event) {
          var row = $(event.target).closest('tr');
          var url = $("input[data-drupal-selector$=uri]", row);
          if (url.val() == '') {
            event.preventDefault();
            event.dataTransfer.dropEffect = "move";
          }
        });
        this.addEventListener('drop', function(event) {
          var row = $(event.target).closest('tr');
          var url = $("input[data-drupal-selector$=uri]", row);
          if (url.val() == '') {
            event.preventDefault();
            var title = $("input[data-drupal-selector$=title]", row);
            title.val(dragChild.getAttribute('data-localgov-title'));
            url.val(dragChild.getAttribute('data-localgov-url'));
            $(dragChild).remove();
          }
        });
      });
    }
  };

  Drupal.behaviors.localgovServiceChildDrop = {
    attach: function attach(context, settings) {
      var linkRow = $("[data-drupal-selector='edit-localgov-destinations'] tr");
      linkRow.each(function() {
        this.addEventListener('dragover', function (event) {
          var row = $(event.target).closest('tr');
          var ref = $("input[data-drupal-selector$=target-id]", row);
          if (ref.val() == '') {
            event.preventDefault();
            event.dataTransfer.dropEffect = "move";
          }
        });
        this.addEventListener('drop', function(event) {
          var row = $(event.target).closest('tr');
          var ref = $("input[data-drupal-selector$=target-id]", row);
          if (ref.val() == '') {
            event.preventDefault();
            ref.val(dragChild.getAttribute('data-localgov-reference'));
            $(dragChild).remove();
          }
        });
      });
    }
  };

  Drupal.behaviors.localgovServiceSubChildDrop = {
    attach: function attach(context, settings) {
      var linkRow = $("[data-drupal-selector$='-subform-topic-list-links'] tr");
      linkRow.each(function() {
        this.addEventListener('dragover', function (event) {
          var row = $(event.target).closest('tr');
          var url = $("input[data-drupal-selector$=uri]", row);
          if (url.val() == '') {
            event.preventDefault();
            event.dataTransfer.dropEffect = "move";
          }
        });
        this.addEventListener('drop', function(event) {
          var row = $(event.target).closest('tr');
          var url = $("input[data-drupal-selector$=uri]", row);
          if (url.val() == '') {
            event.preventDefault();
            var title = $("input[data-drupal-selector$=title]", row);
            title.val(dragChild.getAttribute('data-localgov-title'));
            url.val(dragChild.getAttribute('data-localgov-url'));
            $(dragChild).remove();
          }
        });
      });
    }
  };

  // Account for menus for sticky position.
  $(document).on('drupalViewportOffsetChange', function () {
    $('div.localgov-services-children-list.item-list').css('top', $('body').css('padding-top'));
  });

})(jQuery, Drupal);
