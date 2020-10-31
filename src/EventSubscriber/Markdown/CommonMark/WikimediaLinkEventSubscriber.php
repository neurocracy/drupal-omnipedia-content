<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent;
use Drupal\omnipedia_content\Event\Omnipedia\WikimediaLinkBuildEvent;
use Drupal\omnipedia_content\OmnipediaContentEventInterface;
use Drupal\omnipedia_content\Service\WikimediaLinkInterface;
use League\CommonMark\Inline\Element\Link;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to expand Wikimedia prefixed link URLs to full URLs.
 *
 * @see Drupal\omnipedia_content\Service\WikimediaLinkInterface
 *   The Omnipedia Wikimedia link service which expands prefixed URLs into full
 *   URLs.
 */
class WikimediaLinkEventSubscriber implements EventSubscriberInterface {

  /**
   * The Omnipedia Wikimedia link service.
   *
   * @var \Drupal\omnipedia_content\Service\WikimediaLinkInterface
   */
  protected $wikimediaLink;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_content\Service\WikimediaLinkInterface $wikimediaLink
   *   The Omnipedia Wikimedia link service.
   */
  public function __construct(WikimediaLinkInterface $wikimediaLink) {
    $this->wikimediaLink = $wikimediaLink;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PARSED => 'onCommonMarkDocumentParsed',
    ];
  }

  /**
   * DocumentParsedEvent callback.
   *
   * This expands link URLs with a Wikimedia prefix to full URLs.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent $event
   *   The event object.
   *
   * @param string $eventName
   *   The name of the event being dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Symfony event dispatcher service.
   */
  public function onCommonMarkDocumentParsed(
    DocumentParsedEvent $event,
    string $eventName,
    EventDispatcherInterface $eventDispatcher
  ): void {
    /** @var \League\CommonMark\Block\Element\Document */
    $document = $event->getDocument();

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    // Determine if there are any subscribers/listeners to the Wikimedia link
    // build event here so that we don't have to check on every while iteration.
    /** @var bool */
    $hasListeners = $eventDispatcher->hasListeners(
      OmnipediaContentEventInterface::WIKIMEDIA_LINK_BUILD
    );

    while ($event = $walker->next()) {
      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      // Only stop at Link nodes when we first encounter them.
      if (!($node instanceof Link) || !$event->isEntering()) {
        continue;
      }

      /** @var string */
      $prefixedUrl = $node->getUrl();

      /** @var string */
      $builtUrl = $this->wikimediaLink->buildUrl($prefixedUrl);

      if ($hasListeners) {
        /** @var \Drupal\omnipedia_content\Event\Omnipedia\WikimediaLinkBuildEvent */
        $linkEvent = new WikimediaLinkBuildEvent(
          $node, $prefixedUrl, $builtUrl
        );

        $eventDispatcher->dispatch(
          OmnipediaContentEventInterface::WIKIMEDIA_LINK_BUILD,
          $linkEvent
        );

        /** @var string */
        $alteredPrefixedUrl = $linkEvent->getPrefixedUrl();

        if ($prefixedUrl !== $alteredPrefixedUrl ) {
          $prefixedUrl = $alteredPrefixedUrl;
        }

        /** @var string */
        $alteredBuiltUrl = $linkEvent->getBuiltUrl();

        if ($builtUrl !== $alteredBuiltUrl) {
          $builtUrl = $alteredBuiltUrl;
        }
      }

      if ($prefixedUrl !== $builtUrl) {
        $node->setUrl($builtUrl);
      }
    }
  }

}
