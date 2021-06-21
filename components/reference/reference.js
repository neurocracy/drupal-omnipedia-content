// -----------------------------------------------------------------------------
//   Omnipedia - Content - References (citations, footnotes)
// -----------------------------------------------------------------------------

// This enhances the references with inline functionality via the content pop-up
// component, displaying the text as either a tooltip or an off-canvas panel,
// depending on the screen width.

AmbientImpact.on(['contentPopUp', 'OmnipediaTooltip'], function(
  aiContentPopUp,
  OmnipediaTooltip
) {
AmbientImpact.addComponent('OmnipediaReference', function(
  OmnipediaReference, $
) {
  'use strict';

  /**
   * Link selector to find reference links.
   *
   * @type {String}
   */
  var linkSelector = '.reference__link';

  this.addBehaviour(
    'OmnipediaReference',
    'omnipedia-reference',
    '.layout-container',
    function(context, settings) {
      /**
       * All reference links in context.
       *
       * @type {jQuery}
       */
      var $links = $(this).find(linkSelector);

      // Don't do anything if we can't find any links.
      if ($links.length === 0) {
        return;
      }

      $links.one('contentPopUpContent.OmnipediaReference', function(
        event, $title, $content
      ) {
        /**
         * The current reference link.
         *
         * @type {jQuery}
         */
        var $this = $(this);

        $title.append(Drupal.t('Citation'));

        $content
          // This clones the reference content and inserts it into the
          // $content element, removing the back reference link from
          // the clone.
          .append($($this.attr('href')).clone().contents())
          .find('.references__backreference-link')
            .remove();
      });

      aiContentPopUp.addItems($links);

    },
    function(context, settings, trigger) {
      /**
       * All reference links in context.
       *
       * @type {jQuery}
       */
      var $links = $(this).find(linkSelector);

      // Don't do anything if we can't find any links.
      if ($links.length === 0) {
        return;
      }

      aiContentPopUp.removeItems($links);

      // Remove the handler in case it's still attached, e.g. there was an error
      // somewhere and it didn't get triggered at all.
      $links.off('contentPopUpContent.OmnipediaReference');
    }
  );
});
});
