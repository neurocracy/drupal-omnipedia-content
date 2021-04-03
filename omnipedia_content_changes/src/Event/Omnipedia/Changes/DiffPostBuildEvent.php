<?php

namespace Drupal\omnipedia_content_changes\Event\Omnipedia\Changes;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\Event;

/**
 * Omnipedia changes diff post-build event.
 */
class DiffPostBuildEvent extends Event {

  /**
   * A Symfony DomCrawler instance containing the diff DOM.
   *
   * @var \Symfony\Component\DomCrawler\Crawler
   */
  protected $crawler;

  /**
   * Constructs this event object.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   A Symfony DomCrawler instance containing the diff DOM.
   */
  public function __construct(Crawler $crawler) {
    $this->setCrawler($crawler);
  }

  /**
   * Get the Symfony DomCrawler instance.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   A Symfony DomCrawler instance containing the diff DOM.
   */
  public function getCrawler() {
    return $this->crawler;
  }

  /**
   * Set the Symfony DomCrawler instance.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   A Symfony DomCrawler instance containing the diff DOM.
   */
  public function setCrawler(Crawler $crawler) {
    $this->crawler = $crawler;
  }

}
