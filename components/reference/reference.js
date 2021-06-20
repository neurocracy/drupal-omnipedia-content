// -----------------------------------------------------------------------------
//   Omnipedia - Content - References (citations, footnotes)
// -----------------------------------------------------------------------------

// This enhances the references with inline functionality via the content pop-up
// component, displaying the text as either a tooltip or an off-canvas panel,
// depending on the screen width.

AmbientImpact.on(['contentPopUp'], function(aiContentPopUp) {
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

      aiContentPopUp.addItems($links, {tooltip: {
        insertCallback: function($tooltip, $trigger) {

          /**
           * The ancestor element that tooltips should be placed after.
           *
           * This avoids issues with inheriting formatting and font size from
           * elements that the tooltip may be placed inside of, by placing the
           * tooltip just after all of these elements.
           *
           * Since jQuery().parents() can filter by a provided selector,
           * starting from closest and going up the tree. By using
           * jQuery().last(), we can reduce the set down to the highest level
           * ancestor, i.e. the one that potentially contains one or more of
           * these, i.e. multiple matching parents. For example, if an infobox
           * contains a media element, this will choose the infobox as that's
           * higher up the tree.
           *
           * @type {jQuery}
           *
           * @see https://api.jquery.com/parents/
           */
          var $container = $trigger.parents([
            '.omnipedia-infobox',
            '.omnipedia-media-group',
            '.omnipedia-media',
            'strong',
            'em',
            'sup',
          ].join(',')).last();

          // If one of the above containers contains the trigger, insert the
          // tooltip after the container.
          if ($container.length > 0) {
            $tooltip.insertAfter($container);

            return;
          }

          // If none of the above containers are found, just insert the tooltip
          // after the triggering element.
          $tooltip.insertAfter($trigger);
        }
      }});
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
