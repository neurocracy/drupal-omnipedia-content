<?php

namespace Drupal\omnipedia_content\CommonMark\EventListener;

use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Inline\Element\Link;

/**
 * Wikimedia link event listener; expands links with a prefix to full URLs.
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
 */
class WikimediaLinkEventListener {

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
   * The CommonMark evironment.
   *
   * @var \League\CommonMark\EnvironmentInterface
   */
  protected $environment;

  /**
   * Event listener constructor; saves dependencies.
   *
   * @param \League\CommonMark\EnvironmentInterface $environment
   *   The CommonMark evironment.
   */
  public function __construct(EnvironmentInterface $environment) {
    $this->environment = $environment;
  }

  /**
   * DocumentParsedEvent callback.
   *
   * This expands link URLs with a Wikimedia prefix to full URLs.
   *
   * @param \League\CommonMark\Event\DocumentParsedEvent $event
   *   The document parsed event object.
   */
  public function onDocumentParsed(DocumentParsedEvent $event): void {
    /** @var \League\CommonMark\Block\Element\Document */
    $document = $event->getDocument();

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    while ($event = $walker->next()) {
      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      // Only stop at Link nodes when we first encounter them.
      if (!($node instanceof Link) || !$event->isEntering()) {
        continue;
      }

      /** @var string */
      $url = $node->getUrl();

      /** @var string */
      $newUrl = $this->buildWikimediaUrl($url);

      // if ($this->isWikimediaPrefixUrl($url)) {
      if ($url !== $newUrl) {
        $node->setUrl($newUrl);
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
