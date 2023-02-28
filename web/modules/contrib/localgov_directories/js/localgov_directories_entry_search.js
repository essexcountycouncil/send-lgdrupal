/**
 * @file
 *   Manages channel search boxes on entry nodes.
 */

(function (drupalSettings) {

  Drupal.behaviors.localgovDirectoriesSearch = {
    attach: function attach(context, settings) {
      // Build a select list with options from all the search boxes.
      var form_ids = Object.keys(drupalSettings.localgovDirectories.directoriesSearch);
      var channelsDropdown = document.createElement('select');
      if (form_ids.length > 1) {
        Object.keys(drupalSettings.localgovDirectories.directoriesSearch).forEach( function(form_id) {
          var channel = document.createElement('option');
          channel.value = form_id;
          channel.text = Drupal.checkPlain(drupalSettings.localgovDirectories.directoriesSearch[form_id]);
          channelsDropdown.appendChild(channel);
        });
        // Swap the select list into the title.
        Object.keys(drupalSettings.localgovDirectories.directoriesSearch).forEach( function(form_id) {
          var label = document.getElementById(form_id + '--channel');
          label.innerHTML = channelsDropdown.outerHTML;
          label.childNodes[0].value = form_id;
          // With an event that hides the current, unhides the selected,
          // and keeps the value of the selectors correct for the search
          // they are on.
          label.childNodes[0].addEventListener('change', function() {
            var previousId = label.id.slice(0, -9);
            var previous = document.getElementById(previousId);
            previous.style.display = 'none';
            var selected = document.getElementById(label.childNodes[0].value);
            selected.style.display = 'block';
            label.childNodes[0].value = previousId;
          });
        });
      }

      // Add a back to search results page if the referrer is the same path minus the last /page.
      if (document.URL.substr(0, document.URL.lastIndexOf('/')) == document.referrer.split('?')[0]) {
        var searchForm = document.querySelector('#block-localgov-directories-channel-search-block');
        var returnLink = document.createElement('a');
        returnLink.href = document.referrer;
        returnLink.innerText = Drupal.t('Back to search results');
        once('directory-return-link', searchForm).forEach(function(form) { form.insertBefore(returnLink, form.firstChild) });
      }
    }
  }

})(drupalSettings, once);

