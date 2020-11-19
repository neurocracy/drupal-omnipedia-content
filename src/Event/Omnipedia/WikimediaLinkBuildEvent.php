<?php

namespace Drupal\omnipedia_content\Event\Omnipedia;

use League\CommonMark\Inline\Element\Link;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wikimedia link build event.
 */
class WikimediaLinkBuildEvent extends Event {

  /**
   * The CommonMark Link element object.
   *
   * @var \League\CommonMark\Inline\Element\Link
   */
  protected $link;

  /**
   * The Wikimedia prefixed URL.
   *
   * @var string
   */
  protected $prefixedUrl;

  /**
   * The built URL.
   *
   * @var string
   */
  protected $builtUrl;

  /**
   * The Wikimedia article title for this link.
   *
   * @var string
   */
  protected $articleTitle;

  /**
   * Constructs this event object.
   *
   * @param \League\CommonMark\Inline\Element\Link $link
   *   The CommonMark Link element object.
   *
   * @param string $prefixedUrl
   *   The Wikimedia prefixed URL.
   *
   * @param string $builtUrl
   *   The built URL.
   *
   * @param string $articleTitle
   *   The Wikimedia article title for this link.
   */
  public function __construct(
    Link $link, string $prefixedUrl, string $builtUrl, string $articleTitle
  ) {
    $this->link         = $link;
    $this->prefixedUrl  = $prefixedUrl;
    $this->builtUrl     = $builtUrl;
    $this->articleTitle = $articleTitle;
  }

  /**
   * Get the CommonMark Link element object.
   *
   * @return \League\CommonMark\Inline\Element\Link
   *   The CommonMark Link element object.
   */
  public function getLink() {
    return $this->link;
  }

  /**
   * Get the Wikimedia prefixed URL for this link.
   *
   * @return string
   *   The Wikimedia prefixed URL for this link.
   */
  public function getPrefixedUrl(): string {
    return $this->prefixedUrl;
  }

  /**
   * Set the Wikimedia prefixed URL for this link.
   *
   * @param string $prefixedUrl
   *   The Wikimedia prefixed URL for this link.
   */
  public function setPrefixedUrl(string $prefixedUrl): void {
    $this->prefixedUrl = $prefixedUrl;
  }

  /**
   * Get the built URL for this link.
   *
   * @return string
   *   The built URL for this link.
   */
  public function getBuiltUrl(): string {
    return $this->builtUrl;
  }

  /**
   * Set the built URL for this link.
   *
   * @param string $builtUrl
   *   The built URL for this link.
   */
  public function setBuiltUrl(string $builtUrl): void {
    $this->builtUrl = $builtUrl;
  }

  /**
   * Get the Wikimedia article title for this link.
   *
   * @return string
   *   The Wikimedia article title for this link.
   */
  public function getArticleTitle(): string {
    return $this->articleTitle;
  }

}
