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

      let windowWidth = window.outerWidth;
      const facetsBlock = context.querySelector('.facets-block');
      const facets = context.querySelectorAll(".facets-widget__list");
      const selectedFacets = context.querySelectorAll(
        ".facets-widget input[checked]"
      );

      if (facetsBlock && !facetsBlock.classList.contains('js-processed')) {
        facetsBlock.classList.add('js-processed');
        const facetsBlockContent = facetsBlock.querySelector('.facets-block__content');
        const facetsBlockTitle = facetsBlock.querySelector('.facets-block__title');
        const facetsBlockTrigger = facetsBlock.querySelector('.facets-block__trigger');
        
        function handleFacetsBlockOpen() {
          facetsBlockContent.style.display = 'block';
          facetsBlockTrigger.setAttribute('aria-expanded', 'true');
        }
        
        function handleFacetsBlockClose() {
          facetsBlockContent.style.display = 'none';
          facetsBlockTrigger.setAttribute('aria-expanded', 'false');
        }

        facetsBlockTitle.addEventListener('click', function() {
          const expanded = facetsBlockTrigger.getAttribute('aria-expanded');
          expanded === 'true' ? handleFacetsBlockClose() : handleFacetsBlockOpen();
        })
        
        function handleFacetsBlock() {
          if (window.matchMedia('(min-width: 768px)').matches) {
            handleFacetsBlockOpen();
            facetsBlockTrigger.style.display = 'none';
          } else {
            handleFacetsBlockClose();
            facetsBlockTrigger.style.display = 'block';
          }
        }
        
        handleFacetsBlock();
        window.addEventListener('resize', Drupal.debounce(function() {
          if (windowWidth != window.outerWidth) {
            handleFacetsBlock();
            windowWidth = windowWidth.outerWidth;
          }
        }, 100, false));
      }

      facets.forEach((facet, index) => {
        if (!facet.classList.contains("js-processed")) {
          facet.classList.add("js-processed");
          const expand = facet.previousElementSibling;
          const trigger = expand.querySelector(".facet-group__trigger");
          const facetItems = facet.querySelectorAll(".facet-item");

          // Always keep the first facet expanded
          if (index === 0) {
            facet.style.display = "block";
            trigger.setAttribute("aria-expanded", "true");
          }
          // If there's 3 or fewer facets available, show all the facets in that block.
          else if (facetItems.length <= 3) {
            facet.style.display = "block";
            trigger.setAttribute("aria-expanded", "true");
          }
          
          expand.addEventListener("click", function () {
            const expanded = trigger.getAttribute("aria-expanded");
            if (expanded === "false") {
              facet.style.display = "block";
              trigger.setAttribute("aria-expanded", "true");
            } else {
              facet.style.display = "none";
              trigger.setAttribute("aria-expanded", "false");
            }
          });
        }
      });

      selectedFacets.forEach((selectedFacet) => {
        if (!selectedFacet.classList.contains("js-processed")) {
          selectedFacet.classList.add("js-processed");
          const facetList = selectedFacet.closest(".facets-widget__list");
          const trigger = facetList.previousElementSibling.querySelector(
            ".facet-group__trigger"
          );
          trigger.setAttribute("aria-expanded", "true");
          facetList.style.display = "block";
        }
      });
    },
  };
})(Drupal);
