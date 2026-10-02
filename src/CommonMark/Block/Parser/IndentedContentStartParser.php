<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Block\Parser;

use Drupal\omnipedia_content\CommonMark\Block\Element\IndentedContent;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * Indented content CommonMark start parser.
 *
 * @see \Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark\IndentedContentEventSubscriber
 *   Explains the purpose of this parser.
 */
class IndentedContentStartParser implements BlockStartParserInterface {

  /**
   * {@inheritdoc}
   *
   * @see \League\CommonMark\Block\Parser\IndentedCodeStartParser::tryStart()
   *   Identical to this method other than the parser added at the end, i.e.
   *   new IndentedCodeParser() is now new IndentedContentParser().
   */
  public function tryStart(
    Cursor $cursor,
    MarkdownParserStateInterface $parserState,
  ): ?BlockStart {

    if (!$cursor->isIndented()) {
      return BlockStart::none();
    }

    if ($parserState->getActiveBlockParser()->getBlock() instanceof Paragraph) {
      return BlockStart::none();
    }

    if ($cursor->isBlank()) {
      return BlockStart::none();
    }

    $cursor->advanceBy(Cursor::INDENT_LEVEL, true);

    return BlockStart::of(new IndentedContentParser())->at($cursor);

  }

}
