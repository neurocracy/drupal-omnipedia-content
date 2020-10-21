<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent;
use Drupal\omnipedia_content\Event\Omnipedia\WikimediaLinkBuildEvent;
use Drupal\omnipedia_content\OmnipediaContentEventInterface;
use League\CommonMark\Inline\Element\Link;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to expand Wikimedia prefixed link URLs to full URLs.
 *
 * For example, the following:
 *
 * wikipedia:Anthropogenic_climate_change
 *
 * is expanded into:
 *
 * https://en.wikipedia.org/wiki/Anthropogenic_climate_change
 *
 * @see https://commonmark.thephpleague.com/1.5/customization/event-dispatcher/
 *   CommonMark event dispatcher documentation.
 *
 * @see self::$wikiPrefixes
 *   List of prefixes that are recognized, corresponding to Wikimedia sites.
 *
 * @todo Should this functionality be moved to a service or utility class?
 */
class WikimediaLinkEventSubscriber implements EventSubscriberInterface {

  /**
   * The Wikimedia site prefixes we recognize at the start of link URLs.
   *
   * @var array
   */
  protected $wikiPrefixes = [
    'wikipedia',
    'wikiquote',
    'wiktionary',
    'wikinews',
    'wikisource',
    'wikibooks',
  ];

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
      $builtUrl = $this->buildWikimediaUrl($prefixedUrl);

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

  /**
   * Determine if the provided URL begins with a Wikimedia prefix.
   *
   * @param string $url
   *   The URL to test.
   *
   * @return boolean
   *   True if the $url parameter begins with a Wikimedia prefix and false
   *   otherwise.
   */
  protected function isWikimediaPrefixUrl(string $url): bool {
    /** @var array */
    $urlSplit = \explode(':', $url, 2);

    return isset($urlSplit[0]) && \in_array($urlSplit[0], $this->wikiPrefixes);
  }

  /**
   * Build a Wikimedia URL from the provided prefixed URL.
   *
   * @param string $url
   *   The prefixed Wikimedia URL to build into a full URL.
   *
   * @return string
   *   The built Wikimedia URL if the $url parameter begins with a Wikimedia
   *   prefix. If the $url parameter does not begin with a Wikimedia prefix,
   *   returns $url as-is.
   *
   * @todo i18n
   */
  protected function buildWikimediaUrl(string $url): string {
    if (!$this->isWikimediaPrefixUrl($url)) {
      return $url;
    }

    /** @var array */
    $urlSplit = \explode(':', $url, 2);

    // @todo i18n
    /** @var string */
    $langCode = 'en';

    /** @var string */
    $article = \str_replace(' ', '_', $urlSplit[1]);

    return
      'https://' . $langCode . '.' . $urlSplit[0] . '.org/wiki/' . $article;
  }

}
