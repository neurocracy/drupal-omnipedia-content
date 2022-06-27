<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentPreParsedEvent;
use League\CommonMark\Block\Element\BlockQuote;
use League\CommonMark\Input\MarkdownInput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter CommonMark blockquotes.
 */
class BlockQuoteEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PRE_PARSED =>
        'onCommonMarkDocumentPreParsed',
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PARSED =>
        'onCommonMarkDocumentParsed',
    ];
  }

  /**
   * DocumentPreParsedEvent callback.
   *
   * This finds any lines starting with "&gt; " and replaces that with "> ".
   * This is to work around the Symfony DomCrawler (and PHP's DOM normalization)
   * used in any filters preceding the Markdown filter escaping CommonMark
   * blockquote syntax.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentPreParsedEvent $event
   *   The event object.
   *
   * @todo Assess whether this is a security risk.
   */
  public function onCommonMarkDocumentPreParsed(
    DocumentPreParsedEvent $event
  ): void {

    /** @var string[] */
    $lines = [];

    foreach ($event->getMarkdown()->getLines() as $line) {
      $lines[] = \preg_replace('/(?<=^)&gt;(?=\s+\S)/', '>', $line);
    }

    $event->setMarkdown(new MarkdownInput(\implode("\n", $lines)));

  }

  /**
   * DocumentParsedEvent callback.
   *
   * This merges BlockQuote elements that are immediate siblings.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent $event
   *   The event object.
   */
  public function onCommonMarkDocumentParsed(DocumentParsedEvent $event): void {

    /** @var \League\CommonMark\Block\Element\Document */
    $document = $event->getDocument();

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    /** @var \League\CommonMark\Block\Element\BlockQuote[] */
    $startBlockQuotes = [];

    while ($event = $walker->next()) {

      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      // Save any BlockQuote elements that are not preceded by another
      // BlockQuote but do have a next sibling that is a BlockQuote.
      if (
        $node instanceof BlockQuote && $event->isEntering() &&
        !($node->previous() instanceof BlockQuote) &&
        $node->next() instanceof BlockQuote
      ) {
        $startBlockQuotes[] = $node;
      }

    }

    foreach ($startBlockQuotes as $startBlockQuote) {

      $currentNode = $startBlockQuote;

      /** @var \League\CommonMark\Block\Element\BlockQuote[] */
      $emptyBlockQuotes = [];

      while (
        $currentNode->next() instanceof BlockQuote &&
        $currentNode = $currentNode->next()
      ) {

        // Append all children of this BlockQuote to the BlockQuote at the start
        // of the sequence.
        foreach ($currentNode->children() as $child) {
          $startBlockQuote->appendChild($child);
        }

        // Save the now-empty BlockQuote.
        $emptyBlockQuotes[] = $currentNode;

      }

      // Finally, remove the empty BlockQuotes.
      foreach ($emptyBlockQuotes as $removeNode) {
        $removeNode->detach();
      }

    }

  }

}
