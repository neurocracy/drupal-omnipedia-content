<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Block\Parser;

use Drupal\omnipedia_content\CommonMark\Block\Element\IndentedContent;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Cursor;

/**
 * Indented content CommonMark parser.
 *
 * @see \Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark\IndentedContentEventSubscriber
 *   Explains the purpose of this parser.
 */
class IndentedContentParser extends AbstractBlockContinueParser {

  protected IndentedContent $block;

  /**
   * {@inheritdoc}
   */
  public function __construct() {

    $this->block = new IndentedContent();

  }

  /**
   * {@inheritdoc}
   */
  public function getBlock(): IndentedContent {
    return $this->block;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(): bool {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function canContain(AbstractBlock $childBlock): bool {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function canHaveLazyContinuationLines(): bool {
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function tryContinue(
    Cursor $cursor,
    BlockContinueParserInterface $activeBlockParser,
  ): ?BlockContinue {

    // If this is indented, just advance the cursor and return, thus allowing
    // normal parsing to continue rather than be detected as an indented code
    // block.
    if ($cursor->isIndented()) {

      $cursor->advanceToNextNonSpaceOrTab();

      return BlockContinue::at($cursor);

    }

    return BlockStart::none();

  }

}
