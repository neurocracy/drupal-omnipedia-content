<?php

namespace Drupal\omnipedia_content\Event\Omnipedia;

/**
 * Interface OmnipediaContentEventInterface.
 */
interface OmnipediaContentEventInterface {

  /**
   * A Wikimedia prefixed URL is being built into a full URL.
   *
   * @Event
   *
   * @var string
   */
  public const WIKIMEDIA_LINK_BUILD = 'omnipedia.content.wikimedia_link_build';

}
