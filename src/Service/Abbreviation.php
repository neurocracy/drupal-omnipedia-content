<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Service;

use Drupal\omnipedia_content\Event\Omnipedia\AbbreviationsBuildEvent;
use Drupal\omnipedia_content\Event\Omnipedia\OmnipediaContentEventInterface;
use Drupal\omnipedia_content\Service\AbbreviationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The Omnipedia abbreviation service.
 */
class Abbreviation implements AbbreviationInterface {

  /**
   * A basic regular expression to match terms with.
   *
   * This attempts to match the term only when it's surrounded by non-word
   * characters, white-space, is at the start and/or end of a string, and
   * allowing for variations in suffixes such as 's', 'es', and 'ing'.
   *
   * Note that '%ABBR%' in this pattern is replaced with the actual abbreviation
   * before matching.
   */
  protected const REGEX = '/(?<=^|\s|\W)%ABBR%(?:s|es|ing)?(?=\s|\W|$)/';

  /**
   * Abbreviations for the current request.
   *
   * @var string[]
   */
  protected array $abbreviations = [];

  /**
   * Whether the abbreviations build event has been dispatched this request.
   *
   * @var boolean
   */
  protected bool $abbreviationsBuildEventDispatched = false;

  /**
   * Constructs this service object; saves dependencies.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Symfony event dispatcher service.
   */
  public function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function addAbbreviation(
    string $abbreviation, string $description
  ): void {
    $this->abbreviations[$abbreviation] = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getAbbreviations(): array {

    // Return here if we've already dispatched the build event this request.
    if ($this->abbreviationsBuildEventDispatched === true) {
      return $this->abbreviations;
    }

    // Return here if there are no listeners to the build event.
    if (!$this->eventDispatcher->hasListeners(
      OmnipediaContentEventInterface::ABBREVIATIONS_BUILD
    )) {
      return $this->abbreviations;
    }

    /** @var \Drupal\omnipedia_content\Event\Omnipedia\AbbreviationsBuildEvent */
    $buildEvent = new AbbreviationsBuildEvent($this->abbreviations);

    $this->eventDispatcher->dispatch(
      $buildEvent,
      OmnipediaContentEventInterface::ABBREVIATIONS_BUILD
    );

    $this->abbreviations = $buildEvent->getAbbreviations();

    $this->abbreviationsBuildEventDispatched = true;

    return $this->abbreviations;

  }

  /**
   * {@inheritdoc}
   */
  public function match(string $text): array {

    /** @var array[] */
    $returnMatches = [];

    foreach ($this->getAbbreviations() as $abbreviation => $description) {

      if (!\preg_match_all(
        \str_replace('%ABBR%', \preg_quote($abbreviation), self::REGEX),
        $text, $matches, \PREG_OFFSET_CAPTURE | \PREG_PATTERN_ORDER
      )) {
        continue;
      }

      foreach ($matches[0] as $match) {

        /** @var integer */
        $offset = $match[1];

        $returnMatches[$offset] = [
          // Important: this must be $match[0] and not $abbreviation, as the
          // former will include any stemming/plural/etc. If $abbreviation were
          // to be used, abbreviations with stemming would not be correctly
          // identified as being in parentheses.
          //
          // @todo Can we include both and let the CommonMark event subscriber
          //   mark up abbreviations using the non-stemmed version while testing
          //   for parentheses using the stemmed version?
          'abbreviation'  => $match[0],
          'description'   => $description,
          'offset'        => $offset,
        ];
      }

    }

    // Sort matches by their keys, which in this case are integer string
    // offsets, so that the abbreviations are returned in the order they appear
    // in $text.
    \ksort($returnMatches);

    return $returnMatches;

  }

}
