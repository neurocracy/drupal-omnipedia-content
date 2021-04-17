<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\omnipedia_content\Plugin\Omnipedia\Element\OmnipediaElementBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Infobox element.
 *
 * @OmnipediaElement(
 *   id               = "infobox",
 *   html_element     = "infobox",
 *   render_children  = false,
 *   title            = @Translation("Infobox"),
 *   description      = @Translation("Loosely based on the <a href='https://en.wikipedia.org/wiki/Template:Infobox'>Wikipedia Template:Infobox</a>.")
 * )
 */
class Infobox extends OmnipediaElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_infobox' => [
        'variables' => [
          'type'  => 'undefined',
          'name'  => 'Undefined',
          'items' => [],
        ],
        'template'  => 'omnipedia-infobox',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var string|null */
    $type = $this->elements->attr('type');

    if ($type === null) {
      $this->setError($this->t(
        'Cannot find the <code>type</code> attribute.'
      ));

      $type = self::getTheme()['omnipedia_infobox']['variables']['type'];
    }

    /** @var string|null */
    $name = $this->elements->attr('name');

    if ($name === null) {
      $this->setError($this->t(
        'Cannot find the <code>name</code> attribute.'
      ));

      $name = self::getTheme()['omnipedia_infobox']['variables']['name'];
    }

    /** @var array */
    $items = [];

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $itemElements = $this->elements->filter('item');

    if (\count($itemElements) === 0) {
      $this->setError($this->t(
        'Cannot find any <code>&lt;item&gt;</code> elements.'
      ));
    }

    foreach ($itemElements as $itemElement) {
      $item = [];

      $item['label'] = $itemElement->getAttribute('label');

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $itemCrawler = new Crawler($itemElement);

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $mediaCrawler = $itemCrawler->filter('media');

      // Mark items that contain media for special handling.
      if (\count($mediaCrawler) > 0) {
        $item['isMedia'] = true;

        /** @var \DOMElement */
        $mediaElement = $mediaCrawler->getNode(0);

        // @todo Should these be made configurable, and should these only be
        //   applied if they aren't already set by the author?
        $mediaElement->setAttribute('align', 'none');
        $mediaElement->setAttribute('style', 'frameless');
      } else {
        $item['isMedia'] = false;
      }

      // Recursively convert and render any elements contained in this item.
      $item['value'] = [
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
          $itemCrawler->html()
        ),
      ];

      $items[] = $item;
    }

    return [
      '#theme'  => 'omnipedia_infobox',
      '#type'   => $type,
      '#name'   => $name,
      '#items'  => $items,

      '#attached' => [
        'library'   => ['omnipedia_content/component.infobox'],
      ],
    ];
  }

}
