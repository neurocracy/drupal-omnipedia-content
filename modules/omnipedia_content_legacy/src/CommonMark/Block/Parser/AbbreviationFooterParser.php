<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\CommonMark\Block\Parser;

use Drupal\omnipedia_content_legacy\CommonMark\Block\Element\AbbreviationFooter;
use Drupal\omnipedia_content\Service\AbbreviationInterface;
use League\CommonMark\Block\Parser\BlockParserInterface;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;

/**
 * Legacy footer abbreviation CommonMark parser.
 *
 * @see \Drupal\omnipedia_content_legacy\EventSubscriber\Markdown\CommonMark\AbbreviationFooterEventSubscriber
 *   Explains the purpose of this parser.
 */
class AbbreviationFooterParser implements BlockParserInterface {

  /**
   * Regular expression to match legacy footer abbreviations.
   *
   * @see https://michelf.ca/projects/php-markdown/extra/#abbr
   */
  protected const REGEX = '/^\*\[([^\]]+)\]:\s*(.+?)\s*$/';

  /**
   * The Omnipedia abbreviation service.
   *
   * @var \Drupal\omnipedia_content\Service\AbbreviationInterface
   */
  protected $abbreviation;

  /**
   * Parser constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_content\Service\AbbreviationInterface $abbreviation
   *   The Omnipedia abbreviation service.
   */
  public function __construct(AbbreviationInterface $abbreviation) {
    $this->abbreviation = $abbreviation;
  }

  /**
   * {@inheritdoc}
   *
   * Note that this is not intended to be fully performance optimized, as it
   * calls \League\CommonMark\Cursor::match() and then \preg_match(), the former
   * to determine if we've indeed found a valid PHP Markdown Extra abbreviation
   * and the latter to extract the abbreviation and description, as the former
   * does not expose this. This is acceptable as we don't have many of these
   * footer abbreviations, and they're going to be removed from the wiki content
   * in favour of the attached data abbreviations, so this is purely a stopgap
   * to support legacy content in the meanwhile.
   */
  public function parse(ContextInterface $context, Cursor $cursor): bool {

    if ($cursor->isIndented()) {
      return false;
    }

    if ($cursor->isBlank()) {
      return false;
    }

    if ($cursor->getNextNonSpaceCharacter() !== '*') {
      return false;
    }

    /** @var string|null */
    $nextCharacter = $cursor->peek();

    if ($nextCharacter !== null && $nextCharacter !== '[') {
      return false;
    }

    /** @var \League\CommonMark\Cursor */
    $previousCursor = $cursor->saveState();

    /** @var string|null */
    $abbreviation = $cursor->match(self::REGEX);

    if (empty($abbreviation)) {
      $cursor->restoreState($previousCursor);

      return false;
    }

    if(!\preg_match(self::REGEX, $abbreviation, $matches)) {
      $cursor->restoreState($previousCursor);

      return false;
    }

    // $matches[1] is the abbreviation, and $matches[2] is the full description.

    $this->abbreviation->addAbbreviation($matches[1], $matches[2]);

    $context->addBlock(new AbbreviationFooter());

    return true;

  }

}
