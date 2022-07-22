/**
 * @file JS file for the facets component.
 */

// Small polyfill needed for IE11
// We can remove this when we stop supporting IE11.
if (window.NodeList && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = Array.prototype.forEach;
}

(function lgdFacetsScript(Drupal) {
  Drupal.behaviors.lgdFacets = {
    attach: function (context) {
      context = context || document;
      
      const facets = context.querySelectorAll('.facets-widget__list');
      const selectedFacets = context.querySelectorAll('.facets-widget input[checked]');

      facets.forEach(facet => {
        if (!facet.classList.contains('js-processed')) {
          facet.classList.add('js-processed');
          const expand = facet.previousElementSibling;
          const trigger = expand.querySelector('.facet-group__trigger');
          trigger.addEventListener('click', function() {
            const expanded = trigger.getAttribute('aria-expanded');
            if (expanded === 'false') {
              facet.style.display = 'block';
              trigger.setAttribute('aria-expanded', 'true');
            } else {
              facet.style.display = 'none';
              trigger.setAttribute('aria-expanded', 'false');
            }
          });
        }
      });

      selectedFacets.forEach(selectedFacet => {
        console.log(selectedFacet);
        if (!selectedFacet.classList.contains('js-processed')) {
          selectedFacet.classList.add('js-processed');
          const facetList = selectedFacet.closest('.facets-widget__list');
          const trigger = facetList.previousElementSibling.querySelector('.facet-group__trigger');
          trigger.setAttribute('aria-expanded', 'true');
          facetList.style.display = 'block';
        }
      });
  
    }
  };
}(Drupal));