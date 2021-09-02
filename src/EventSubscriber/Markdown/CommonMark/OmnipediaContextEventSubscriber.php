<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentPreParsedEvent;
use League\CommonMark\Input\MarkdownInput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to provide Omnipedia context data to CommonMark documents.
 *
 * This subscribes to the CommonMark DocumentPreParsedEvent, where it searches
 * the raw Markdown for <omnipedia-context> elements. If any are found, their
 * "context" attributes are added to the $document->data['omnipediaContext']
 * array, and the elements are removed from the Markdown text. This allows for
 * CommonMark event subscribers to make decisions about how to render or alter
 * content based on the context they're being rendered in. Note that this is not
 * the same as the Drupal core concept of a render context.
 *
 * @see https://www.drupal.org/project/drupal/issues/226963
 *   Drupal core issue to add context data to input filters. Has not had a new
 *   patch created since Drupal core 8.1 at the time of writing.
 */
class OmnipediaContextEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PRE_PARSED =>
        'onCommonMarkDocumentPreParsed',
    ];
  }

  /**
   * DocumentPreParsedEvent callback.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentPreParsedEvent $event
   *   The event object.
   */
  public function onCommonMarkDocumentPreParsed(
    DocumentPreParsedEvent $event
  ): void {

    /** @var \League\CommonMark\Block\Element\Document */
    $document = $event->getDocument();

    /** @var string[] */
    $foundContexts = [];

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = new Crawler('<omnipedia-context-root>' .
      $event->getMarkdown()->getContent() .
    '</omnipedia-context-root>');

    foreach ($crawler->filter('omnipedia-context') as $element) {

      if ($element->hasAttribute('context')) {

        $foundContexts[] = $element->getAttribute('context');

      }

      $element->parentNode->removeChild($element);

    }

    // If contexts were found, save the altered Markdown. This saves a bit of
    // work in cases where no context was present.
    if (!empty($foundContexts)) {

      $event->setMarkdown(new MarkdownInput(
        $crawler->filter('omnipedia-context-root')->html()
      ));

      foreach ($foundContexts as $foundContext) {
        $document->data['omnipediaContext'][] = $foundContext;
      }

    }

    // Provide a 'none' context if no context was provided so that the data is
    // always guaranteed to exist.
    if (empty($document->data['omnipediaContext'])) {
      $document->data['omnipediaContext'] = ['none'];
    }

  }

}
