<?php

namespace Drupal\omnipedia_content\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provides a filter to alter Markdown filter output.
 *
 * This does the following:
 *
 * - Adds "reference" class to <sup> elements containing .reference__link
 *   elements.
 *
 * - Works around @link https://github.com/thephpleague/commonmark/issues/596
 *   an issue with CommonMark @endLink where our References heading text
 *   isn't being generated into the table of contents, resulting in an empty
 *   link.
 *
 * @Filter(
 *   id           = "omnipedia_markdown_alterations",
 *   title        = @Translation("Omnipedia: Markdown output alterations"),
 *   description  = @Translation("This alters the output of the Markdown filter. This should be placed <strong>after</strong> the Markdown filter in the processing order."),
 *   type         = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 *
 * @see \Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension\FootnoteExtension
 *   Alters the Markdown footnotes output as much as possible in CommonMark
 *   document parsed event.
 */
class MarkdownAlterationsFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {
    /** @var \Symfony\Component\DomCrawler\Crawler */
    $rootCrawler = new Crawler(
      // The <div> is to prevent the PHP DOM automatically wrapping any
      // top-level text content in a <p> element.
      '<div id="omnipedia-markdown-alterations-filter-root">' .
        (string) $text .
      '</div>'
    );

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $referenceSupCrawler = $rootCrawler
      ->filter('.reference__link')
      ->evaluate('./ancestor::sup');

    foreach ($referenceSupCrawler as $sup) {
      $sup->setAttribute('class', 'reference');
    }

    // Try to find the first heading preceding the .references container at the
    // end of the document.
    /** @var array */
    $referencesHeadingResult = $rootCrawler
      ->filter('.references')
      // This selects the nearest preceding heading, regardless of what heading
      // level it is. This assumes that that's the References heading.
      //
      // @see https://stackoverflow.com/questions/30775686/xpath-get-closest-heading-element-h1-h2-h3-etc
      ->evaluate(
        './preceding-sibling::*[' . \implode(' or ', [
          'self::h1',
          'self::h2',
          'self::h3',
          'self::h4',
          'self::h5',
          'self::h6',
        ]) . '][1]'
      );

    foreach ($referencesHeadingResult as $heading) {
      /** @var \Symfony\Component\DomCrawler\Crawler */
      $permalinkCrawler = new Crawler($heading);

      // Attempt to find the permalink in the heading.
      /** @var \DOMElement|null */
      $permalink = $permalinkCrawler->filter('.heading-permalink')->getNode(0);

      if ($permalink === null) {
        break;
      }

      /** @var string|null */
      $permalinkName  = $permalink->getAttribute('name');
      /** @var string|null */
      $permalinkId    = $permalink->getAttribute('id');

      if (empty($permalinkName) || empty($permalinkId)) {
        break;
      }

      // Try and find the corresponding link in the table of contents.
      /** @var \DOMElement|null */
      $tableOfContentsLink = $rootCrawler
        ->filter('.table-of-contents a[href="#' . $permalinkId . '"]')
        ->getNode(0);

      if ($tableOfContentsLink === null) {
        break;
      }

      $tableOfContentsLink->nodeValue = $permalinkName;
    }

    return new FilterProcessResult(
      $rootCrawler->filter(
        '#omnipedia-markdown-alterations-filter-root'
      )->html()
    );
  }

}
