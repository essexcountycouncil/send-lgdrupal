(function equalHeightsBlocksScript(Drupal) {
  Drupal.behaviors.equalHeightsBlocks = {
    attach: function (context) {
      context = context || document;

      const layouts = Array.from(context.querySelectorAll('.layout'));
      const layoutsWithIaBlocks = layouts.filter(item => item.querySelector('.ia-block'));
      const layoutsWithCallOutBox = layouts.filter(item => item.querySelector('.call-out-box'));
      const layoutsWithTeaserBlocks = layouts.filter(item => item.querySelector('.featured-teaser'));

      function equaliseHeightsOfTheseBlocks(layoutWithBlocks, typeOfBlock) {
        layoutWithBlocks.forEach(item => {
          const blocksToEqualiseHeights = item.querySelectorAll(typeOfBlock);
          const blockHeights = [];

          function handleGetHeights() {
            blocksToEqualiseHeights.forEach(block => {
              blockHeights.push(block.offsetHeight);
            });
            let tallestBlock = Math.max(...blockHeights);
            blocksToEqualiseHeights.forEach(block => {
              block.style.height = `${tallestBlock}px`;
            });
          }

          handleGetHeights();
        })
      }

      layoutsWithCallOutBox.forEach(layout => {
        equaliseHeightsOfTheseBlocks(layoutsWithCallOutBox, '.call-out-box');
      });

      layoutsWithIaBlocks.forEach(layout => {
        equaliseHeightsOfTheseBlocks(layoutsWithIaBlocks, '.ia-block');
      });

      layoutsWithTeaserBlocks.forEach(layout => {
        equaliseHeightsOfTheseBlocks(layoutsWithTeaserBlocks, '.featured-teaser');
      });

    }
  };
}(Drupal));
