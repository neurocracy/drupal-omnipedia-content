<?php

namespace Drupal\omnipedia_content\CommonMark\Block\Renderer;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;

/**
 * Indented content CommonMark renderer.
 *
 * @see \Drupal\omnipedia_content\Plugin\Markdown\CommonMark\CommonMark
 *   Explains the purpose of this renderer.
 */
class IndentedContentRenderer implements BlockRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function render(
    AbstractBlock $block,
    ElementRendererInterface $htmlRenderer,
    bool $inTightList = false
  ) {
    // This just renders the children in place, without a containing element.
    //
    // @todo Can we return a \League\CommonMark\HtmlElement like the
    //   documentation recommends as a best practice? How do we do that without
    //   an element/tag name?
    return $htmlRenderer->renderBlocks($block->children());
  }

}
