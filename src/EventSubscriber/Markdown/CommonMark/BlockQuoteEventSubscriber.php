<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentPreParsedEvent;
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
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PRE_PARSED => 'onCommonMarkDocumentPreParsed',
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

}
