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

    // If one of the above containers contains the trigger, insert the tooltip
    // after the container.
    if ($container.length > 0) {

      /**
       * The next sibling node to the container, or null if none.
       *
       * @type {HTMLElement|null}
       */
      let nextSibling = $container[0].nextSibling;

      // If the next sibling is a text node, insert the tooltip after it rather
      // than the container. This fixes an issue in Chrome that could cause
      // white-space after the container to collapse the first time that a
      // tooltip would be inserted right after the container.
      if (nextSibling !== null && nextSibling.nodeName === '#text') {

        $tooltip.insertAfter(nextSibling);

        return;

      }

      $tooltip.insertAfter($container);

      return;

    }

    // If none of the above containers are found, just insert the tooltip after
    // the triggering element.
    $tooltip.insertAfter($trigger);

  }

});
});
