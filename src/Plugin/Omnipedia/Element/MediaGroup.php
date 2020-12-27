<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\omnipedia_content\OmnipediaElementBase;

/**
 * Media group element.
 *
 * @OmnipediaElement(
 *   id               = "media_group",
 *   html_element     = "media-group",
 *   render_children  = false,
 *   title            = @Translation("Media group"),
 *   description      = @Translation("Media group element.")
 * )
 */
class MediaGroup extends OmnipediaElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_media_group' => [
        'variables' => [
          'items'     => [],
          'caption'   => '',
          'align'     => 'right',
          'view_mode' => 'omnipedia_embedded_group',
        ],
        'template'  => 'omnipedia-media-group',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var \Symfony\Component\DomCrawler\Crawler */
    $mediaElements = $this->elements->filter('media');

    /** @var array */
    $items = [];

    foreach ($mediaElements as $mediaElement) {
      // @todo Should these be made configurable, and should these only be
      //   applied if they aren't already set by the author?
      $mediaElement->setAttribute('align', 'none');
      $mediaElement->setAttribute('style', 'frameless');
      $mediaElement->setAttribute(
        'view-mode',
        self::getTheme()['omnipedia_media_group']['variables']['view_mode']
      );

      // Recursively convert and render any elements contained in this item.
      $items[] = [
        // This bypasses any further rendering, including XSS filtering - which
        // strips 'style' attributes that are needed for inline max-widths on
        // image fields to function correctly.
        //
        // @todo Is this a security risk, given that the generated markup has
        //   already been rendered in the element mananger via Drupal's
        //   renderer?
        //
        // @see \Drupal\Component\Utility\Xss::attributes()
        //   Strips 'style' attributes.
        '#printed'  => true,
        '#markup'   => $this->elementManager->convertElements(
          $mediaElement->ownerDocument->saveHTML($mediaElement)
        ),
      ];
    }

    /** @var string|null */
    $align = $this->elements->attr('align');

    // @todo Remove this when we have default options/attributes implemented.
    if ($align === null) {
      $align = self::getTheme()['omnipedia_media_group']['variables']['align'];
    }

    /** @var array */
    $renderArray = [
      '#theme'      => 'omnipedia_media_group',

      '#items'      => $items,
      '#align'      => $align,

      '#attached'   => [
        'library'     => ['omnipedia_content/component.media_group'],
      ],
    ];

    /** @var string|null */
    $caption = $this->elements->attr('caption');

    if (!empty($caption)) {
      $renderArray['#caption'] = $caption;
    }

    return $renderArray;
  }

}
