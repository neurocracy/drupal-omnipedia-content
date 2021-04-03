<?php

namespace Drupal\omnipedia_content_changes\Event;

/**
 * Interface OmnipediaContentChangesEventInterface.
 */
interface OmnipediaContentChangesEventInterface {

  /**
   * Alter diff content after it's built.
   *
   * @Event
   *
   * @var string
   */
  public const DIFF_POST_BUILD = 'omnipedia.content_changes.diff_post_build';

}
