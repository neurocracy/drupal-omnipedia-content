<?php

namespace Drupal\omnipedia_content_legacy\Service;

use Symfony\Component\DomCrawler\Crawler;

/**
 * The Omnipedia legacy Markdown preparer service.
 */
class MarkdownPreparer {

  /**
   * Process content.
   *
   * This converts any <ul> and <ol> elements from HTML elements to Markdown
   * lists so that the list item contents are processed by the CommonMark
   * parser.
   *
   * @param string $content
   *   Content to process.
   *
   * @return string
   *   The content with any processing applied.
   */
  public function process(string $content): string {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $rootCrawler = new Crawler(
      // The <div> is to prevent the PHP DOM from automatically wrapping any
      // top-level text content in a <p> element.
      '<div id="omnipedia-markdown-preparer-root">' . $content . '</div>'
    );

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $ulCrawler = $rootCrawler->filter('ul');

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $olCrawler = $rootCrawler->filter('ol');

    // Return $content if no lists are found to avoid unnecessary processing.
    if (count($ulCrawler) === 0 && count($olCrawler) === 0) {
      return $content;
    }

    foreach ($ulCrawler as $ul) {

      foreach ((new Crawler($ul))->children() as $listItem) {

        $ul->parentNode->insertBefore(
          $listItem->ownerDocument->createTextNode(
            '* ' . $listItem->textContent . "\n"
          ),
          $ul
        );

      }

      $ul->parentNode->removeChild($ul);

    }

    foreach ($olCrawler as $ol) {

      /** @var integer */
      $counter = 1;

      foreach ((new Crawler($ol))->children() as $listItem) {

        $ol->parentNode->insertBefore(
          $listItem->ownerDocument->createTextNode(
            $counter . '. ' . $listItem->textContent . "\n"
          ),
          $ol
        );

        $counter++;

      }

      $ol->parentNode->removeChild($ol);

    }

    return $rootCrawler->filter('#omnipedia-markdown-preparer-root')->html();

  }

}
