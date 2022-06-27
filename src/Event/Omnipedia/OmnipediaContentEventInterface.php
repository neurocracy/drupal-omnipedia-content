<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Event\Omnipedia;

/**
 * Interface OmnipediaContentEventInterface.
 */
interface OmnipediaContentEventInterface {

  /**
   * Abbreviations are being built for the request.
   *
   * @Event
   *
   * @var string
   */
  public const ABBREVIATIONS_BUILD = 'omnipedia.content.abbreviations_build';

  /**
   * A Wikimedia prefixed URL is being built into a full URL.
   *
   * @Event
   *
   * @var string
   */
  public const WIKIMEDIA_LINK_BUILD = 'omnipedia.content.wikimedia_link_build';

}
