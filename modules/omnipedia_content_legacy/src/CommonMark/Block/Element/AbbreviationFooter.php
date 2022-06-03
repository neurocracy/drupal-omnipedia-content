<?php

namespace Drupal\omnipedia_content_legacy\CommonMark\Block\Element;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;

/**
 * Legacy footer abbreviation CommonMark element.
 *
 * @see \Drupal\omnipedia_content_legacy\EventSubscriber\Markdown\CommonMark\AbbreviationFooterEventSubscriber
 *   Explains the purpose of this element.
 */
class AbbreviationFooter extends AbstractBlock {

  /**
   * {@inheritdoc}
   */
  public function canContain(AbstractBlock $block): bool {
    return false;
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
    return false;
  }

  /**
   * Render this element.
   *
   * In this case, render means output nothing to remove the footer abbreviation
   * from the document, as it's been parsed and sent to the abbreviation
   * service.
   *
   * @return string
   */
  public function render(): string {
    return '';
  }

}
