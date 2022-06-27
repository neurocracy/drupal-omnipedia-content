<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\Service;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The Omnipedia legacy Freelinking links to Markdown converter service.
 */
class FreelinkingToMarkdown {

  use StringTranslationTrait;

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs this service object; saves dependencies.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal messenger service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    MessengerInterface    $messenger,
    TranslationInterface  $stringTranslation
  ) {
    $this->messenger          = $messenger;
    $this->stringTranslation  = $stringTranslation;
  }

  /**
   * Process content.
   *
   * This converts legacy Freelinking links to Markdown link syntax.
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
      '<div id="omnipedia-freelinking-to-markdown-root">' . $content . '</div>'
    );

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $freelinksCrawler = $rootCrawler->filter(
      'drupal-filter-placeholder[callback="freelinking.manager:createFreelinkElement"]'
    );

    foreach ($freelinksCrawler as $element) {
      /** @var array */
      $parsedArguments = UrlHelper::parse(
        '?' . $element->getAttribute('arguments')
      );

      if (empty($parsedArguments['query'])) {
        $this->messenger->addError($this->t(
          'Could not parse the following Freelink array: <pre>@array</pre>',
          ['@array' => $element->getAttribute('arguments')]
        ));

        continue;
      }

      // If the Freelinking link is a Wikimedia link, build our Markdown
      // equivalent.
      if ($parsedArguments['query'][0] === 'wiki') {
        /** @var array */
        $linkParts = \explode('|', $parsedArguments['query'][1]);

        /** @var string */
        $linkContent = $linkParts[1];

        /** @var string */
        $linkUrl = $parsedArguments['query'][2] . ':' .
          // Note that CommonMark will not recognize this as a link if there's a
          // space in the URL, so we have to replace them with underscores.
          \str_replace(' ', '_', $linkParts[0]);

      // Otherwise, assume that it's a link to another wiki page on this site
      // and build the URL for that.
      } else {
        /** @var array */
        $linkParts = \explode('|', $parsedArguments['query'][1]);

        /** @var string */
        $linkContent = $linkParts[1];

        /** @var string */
        $linkUrl = Url::fromUserInput('/wiki/' . $linkParts[0])->toString();
      }

      // We need to find the new node's parent to use the replaceChild()
      // method, awkward though it may be.
      /** @var \DOMNode|null */
      $elementParent = $element->parentNode;

      if ($elementParent === null) {
        $this->messenger->addError($this->t(
          'Could not find a valid parent node for the following Freelink array: <pre>@array</pre>',
          ['@array' => $element->getAttribute('arguments')]
        ));

        continue;
      }

      // Replace the old node (the Freelinking filter placeholder) with a
      // Markdown link.
      $elementParent->replaceChild(
        // New node.
        $element->ownerDocument->createTextNode(
          '[' . $linkContent . '](' . $linkUrl . ')'
        ),
        // Old node.
        $element
      );
    }

    return $rootCrawler->filter('#omnipedia-freelinking-to-markdown-root')
      ->html();

  }

}
