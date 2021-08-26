// -----------------------------------------------------------------------------
//   Omnipedia - Content - Media image component
// -----------------------------------------------------------------------------

// This finds all Omnipedia media that have a link with a 'data-photowipe-src'
// attribute that contain a thumbnail image whose 'currentSrc' property points
// to a WebP image (which indicates the browser has chosen and supports WebP),
// and alters the 'data-photoswipe-src' attribute to point to a WebP derivative
// image.
//
// @todo Generalize this so we don't have to hard code WebP or any other format
//   but have it set on the <source> elements via the backend and automatically
//   set the 'data-photoswipe-src' on the link to the value of a
//   'data-photoswipe-src' on the <source> that's been chosen by the browser as
//   detected from the currentSrc of the <img>?
//
// @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/currentSrc

AmbientImpact.addComponent(
  'OmnipediaMediaImage',
function(OmnipediaMediaImage, $) {

  'use strict';

  /**
   * Get the file extension from a file/path.
   *
   * @param {String} fileName
   *
   * @return {String}
   *
   * @see https://stackoverflow.com/a/12900504
   *   Based on this Stack Overflow answer.
   */
  function getFileExtension(fileName) {
    return fileName.slice((fileName.lastIndexOf('.') - 1 >>> 0) + 2);
  };

  this.addBehaviour(
    'OmnipediaMediaImage',
    'omnipedia-media-image',
    '.layout-container',
    function(context, settings) {

      /**
       * Media element links that point to a PhotoSwipe image.
       *
       * @type {jQuery}
       */
      var $mediaImageLinks = $(
        '.omnipedia-media a[data-photoswipe-src]', context
      );

      for (var i = $mediaImageLinks.length - 1; i >= 0; i--) {

        /**
         * The currentSrc property of the first image in the link.
         *
         * @type {String|undefined}
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/currentSrc
         */
        var currentSrc = $mediaImageLinks.eq(i).find('img').eq(0).prop(
          'currentSrc'
        );

        if (typeof currentSrc === 'undefined') {
          continue;
        }

        // Skip this link if the thumbnail loaded/chosen by the browser isn't a
        // WebP image.
        if (getFileExtension(new URL(currentSrc).pathname) !== 'webp') {
          continue;
        }

        // Save the unaltered data-photoswipe-src to element data for detach to
        // undo the changes.
        $mediaImageLinks.eq(i).data(
          'oldPhotoSwipeSrc', $mediaImageLinks.eq(i).data('photoswipeSrc')
        );

        /**
         * The URL object representing the 'data-photoswipe-src' link attribute.
         *
         * @type {URL}
         */
        var photoswipeSrcUrl = new URL(
          $mediaImageLinks.eq(i).data('photoswipeSrc')
        );

        /**
         * The file extension of the 'data-photoswipe-src' link attribute.
         *
         * @type {String}
         */
        var photoswipeSrcExtension = getFileExtension(
          photoswipeSrcUrl.pathname
        );

        // Alter the original PhotoSwipe URL to use the WebP file extension.
        photoswipeSrcUrl.pathname = photoswipeSrcUrl.pathname.slice(
          0, photoswipeSrcExtension.length * -1
        ) + 'webp';

        // Save the altered URL for PhotoSwipe to load.
        $mediaImageLinks.eq(i).attr(
          'data-photoswipe-src', photoswipeSrcUrl.toString()
        );

      }

    },
    function(context, settings, trigger) {

      /**
       * Media element links that point to a PhotoSwipe image.
       *
       * @type {jQuery}
       */
      var $mediaImageLinks = $(
        '.omnipedia-media a[data-photoswipe-src]', context
      );

      for (var i = $mediaImageLinks.length - 1; i >= 0; i--) {

        // Restore the original data-photoswipe-src attribute and remove our
        // stored data.
        $mediaImageLinks.eq(i).attr(
          'data-photoswipe-src', $mediaImageLinks.eq(i).data('oldPhotoSwipeSrc')
        ).removeData('oldPhotoSwipeSrc');

      }

    }
  );

});
