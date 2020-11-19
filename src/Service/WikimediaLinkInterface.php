<?php

namespace Drupal\omnipedia_content\Service;

/**
 * The Omnipedia Wikimedia link service interface.
 *
 * This provides functionality to parse URLs like the following:
 *
 * wikipedia:Anthropogenic_climate_change
 *
 * and expand them into:
 *
 * https://en.wikipedia.org/wiki/Anthropogenic_climate_change
 *
 * @see https://en.wikipedia.org/wiki/Interwiki_links
 *   Our Wikimedia prefix URLs are similar in concept to interwiki links in
 *   MediaWiki.
 */
interface WikimediaLinkInterface {

  /**
   * Get all supported Wikimedia link prefixes.
   *
   * @return array
   */
  public function getSupportedPrefixes(): array;

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
  public function isPrefixUrl(string $url): bool;

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
  public function buildUrl(string $url): string;

  /**
   * Get the article title from the provided prefixed URL.
   *
   * @param string $url
   *   The prefixed Wikimedia URL to get the article title for.
   *
   * @return string|null
   *   The article title as a string, or null if the provided URL is not a valid
   *   Wikimedia prefixed URL.
   */
  public function getArticleTitleFromPrefixedUrl(string $url): ?string;

}
