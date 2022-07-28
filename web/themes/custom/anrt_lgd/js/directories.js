/**
 * @file JS file for the facets component.
 */

// Small polyfill needed for IE11
// We can remove this when we stop supporting IE11.
if (window.NodeList && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = Array.prototype.forEach;
}

(function lgdDirectoriesScript(Drupal) {
  Drupal.behaviors.lgdDirectories = {
    attach: function (context) {
      context = context || document;
      
      const sortTypeSelector = context.querySelector('.page-node-type-localgov-directory .block-localgov-directories-channel-search-block .form-item-sort-by');
      const sortTypeOrder = context.querySelector('.page-node-type-localgov-directory .block-localgov-directories-channel-search-block .form-item-sort-order');
      if (!sortTypeSelector && !sortTypeOrder) {
        return;
      }

      if (sortTypeSelector.classList.contains('js-processed')) {
        return;
      } else {
        sortTypeSelector.classList.add('visually-hidden');
        sortTypeSelector.classList.add('js-processed');
      }
      if (sortTypeOrder.classList.contains('js-processed')) {
        return;
      } else {
        sortTypeOrder.querySelector('label').classList.add('visually-hidden');
        if (sortTypeSelector.querySelector('select').value == 'localgov_directory_title_sort') {
          const options = sortTypeOrder.querySelectorAll('option');
          console.log(options);
          options.forEach(option => {
            if (option.value === 'ASC') {
              option.innerText = 'A - Z';
            }
            if (option.value === 'DESC') {
              option.innerText = 'Z - A';
            }
          })
        }
        sortTypeOrder.classList.add('js-processed');
      }
    }
  };
}(Drupal));