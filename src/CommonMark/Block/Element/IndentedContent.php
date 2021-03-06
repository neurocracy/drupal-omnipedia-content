<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Block\Element;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;

/**
 * Indented content CommonMark element.
 *
 * @see \Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark\IndentedContentEventSubscriber
 *   Explains the purpose of this element.
 */
class IndentedContent extends AbstractBlock {

  /**
   * {@inheritdoc}
   */
  public function canContain(AbstractBlock $block): bool {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function isCode(): bool {
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function matchesNextLine(Cursor $cursor): bool {
    // If this is indented, just advance the cursor and return true, thus
    // allowing normal parsing to continue rather than be detected as an
    // indented code block.
    if ($cursor->isIndented()) {
      $cursor->advanceToNextNonSpaceOrTab();

      return true;
    }

    return false;
  }

}
