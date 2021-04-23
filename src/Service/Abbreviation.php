<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\omnipedia_content\Service\AbbreviationInterface;

/**
 * The Omnipedia abbreviation service.
 */
class Abbreviation implements AbbreviationInterface {

  /**
   * A basic regular expression to match terms with.
   *
   * This attempts to match the term only when it's surrounded by non-word
   * characters or white-space, and allowing for variations in suffixes such as
   * 's', 'es', and 'ing'.
   *
   * Note that '%ABBR%' in this pattern is replaced with the actual abbreviation
   * before matching.
   */
  protected const REGEX = '/(?<=\s|\W)%ABBR%(?=s|es|ing|\s|\W)/';

  /**
   * Abbreviations for the current request.
   *
   * @var string[]
   */
  protected $abbreviations = [];

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
    return $this->abbreviations;
  }

  /**
   * {@inheritdoc}
   */
  public function match(string $text): array {

    /** @var array[] */
    $returnMatches = [];

    foreach ($this->abbreviations as $abbreviation => $description) {

      if (!\preg_match_all(
        \str_replace('%ABBR%', \preg_quote($abbreviation), self::REGEX),
        $text, $matches, \PREG_OFFSET_CAPTURE | \PREG_PATTERN_ORDER
      )) {
        continue;
      }

      foreach ($matches[0] as $match) {
        $returnMatches[$match[1]] = [
          'abbreviation'  => $abbreviation,
          'description'   => $description,
          'offset'        => $match[1],
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
