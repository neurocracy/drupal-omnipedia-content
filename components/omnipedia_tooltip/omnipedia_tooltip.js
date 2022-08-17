// -----------------------------------------------------------------------------
//   Omnipedia - Content - Tooltip component
// -----------------------------------------------------------------------------

// This provides a tooltip insert callback that attempts to place tooltips
// outside of all elements that have styles that would cause inconsistencies
// between different tooltips, such as changes in font size or layout.

AmbientImpact.on(['tooltip'], function(aiTooltip) {
AmbientImpact.addComponent('OmnipediaTooltip', function(OmnipediaTooltip, $) {

  'use strict';

  // Bail if a default insert callback has been defined.
  //
  // @todo Should we override this regardless?
  if (typeof aiTooltip.defaults.insertCallback !== 'undefined') {
    return;
  }

  /**
   * An array of container element selectors to place tooltips outside of.
   *
   * These elements have styles that would cause inconsistencies between
   * tooltips, such as changes in font size or layout. This list of selectors is
   * used to ensure tooltips are inserted outside of all of these elements,
   * which avoids inheriting their styles.
   *
   * @type {Array}
   */
  this.containerSelectors = [
    '.omnipedia-infobox',
    '.omnipedia-media-group',
    '.omnipedia-media',
    'blockquote',
    'strong',
    'em',
    'sup',
  ];

  aiTooltip.defaults.insertCallback = function($tooltip, $trigger) {

    /**
     * The element to insert the tooltip after, in a jQuery collection.
     *
     * This defaults to the $trigger if no container is found below.
     *
     * @type {jQuery}
     */
    let $insertAfter = $trigger;

    /**
     * The ancestor element that tooltips should be placed after.
     *
     * Since jQuery().parents() can filter by a provided selector,
     * starting from closest and going up the tree, we can limit results to only
     * the selectors we're looking to insert outside of. By using
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
    let $container = $trigger.parents(
      OmnipediaTooltip.containerSelectors.join(',')
    ).last();

    if ($container.length > 0) {

      // If one of the above containers contains the trigger, set $insertAfter
      // to the found container.
      $insertAfter = $container;

      /**
       * The next sibling node to the container, or null if none.
       *
       * @type {HTMLElement|null}
       */
      let nextSibling = $insertAfter[0].nextSibling;

      // If the next sibling is a text node, set $insertAfter to that text node
      // rather than the container.
      //
      // This fixes an issue in Chrome that could cause white-space after the
      // container to collapse the first time that a tooltip would be inserted
      // right after the container.
      //
      // Note that this should only be applied when a container is found, as it
      // can cause white-space reflow (ironically enough) if applied if no
      // container is present in Chrome.
      if (nextSibling !== null && nextSibling.nodeName === '#text') {
        $insertAfter = $(nextSibling);
      }

    }

    $tooltip.insertAfter($insertAfter);

  }

});
});
