<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Event\Omnipedia;

use Symfony\Component\EventDispatcher\Event;

/**
 * Abbreviations build event.
 */
class AbbreviationsBuildEvent extends Event {

  /**
   * Any abbreviations added to this event by subscribers.
   *
   * Keys are the abbreviated term and values are the description of that term.
   *
   * @var string[]
   */
  protected array $abbreviations = [];

  /**
   * Constructs this event object.
   *
   * @param array $abbreviations
   *   An array of abbreviations; keys are the abbreviated term and values are
   *   the description of that term.
   */
  public function __construct(array $abbreviations) {
    $this->abbreviations = $abbreviations;
  }

  /**
   * Add abbreviations to the event.
   *
   * @param array $abbreviations
   *   An array of abbreviations; keys are the abbreviated term and values are
   *   the description of that term.
   */
  public function addAbbreviations(array $abbreviations): void {
    $this->abbreviations = $abbreviations + $this->abbreviations;
  }

  /**
   * Get abbreviations added to this event.
   *
   * @return array
   *   An array of abbreviations; keys are the abbreviated term and values are
   *   the description of that term.
   */
  public function getAbbreviations(): array {
    return $this->abbreviations;
  }

}
