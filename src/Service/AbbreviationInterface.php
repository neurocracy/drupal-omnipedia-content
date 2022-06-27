<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Service;

/**
 * The Omnipedia abbreviation service interface.
 */
interface AbbreviationInterface {

  /**
   * Add an abbreviation.
   *
   * @param string $abbreviation
   *   The abbreviated text, e.g. "HTML".
   *
   * @param string $description
   *   The description or full text of the provided abbreviation, e.g.
   *   "HyperText Markup Language".
   */
  public function addAbbreviation(
    string $abbreviation, string $description
  ): void;

  /**
   * Get all abbreviations defined during this request.
   *
   * @return array
   *   An array of abbreviations as keys to description values.
   */
  public function getAbbreviations(): array;

 /**
   * Match existing abbreviations to the provided text content.
   *
   * @param string $text
   *   The text content to match abbreviations to.
   *
   * @return array[]
   *   An array containing an entry for each abbreviation found, in the order
   *   they appear in the $text parameter. Each entry is an associative array
   *   containing the following:
   *
   *   - 'abbreviation': This is the abbreviation term matched; for example,
   *     'HTML'.
   *
   *   - 'description': The description for the abbreviation, i.e. the expanded
   *     abbreviation; for example, 'HyperText Markup Language'.
   *
   *   - 'offset': The string offset where the abbreviation starts in the $text
   *     parameter.
   *
   *   Note that there can and will be multiple entries for the same term, one
   *   for each occurrance in $text.
   */
  public function match(string $text): array;

}
