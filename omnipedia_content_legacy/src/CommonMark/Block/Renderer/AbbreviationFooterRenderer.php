<?php

namespace Drupal\omnipedia_content_legacy\CommonMark\Block\Renderer;

use Drupal\omnipedia_content_legacy\CommonMark\Block\Element\AbbreviationFooter;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;

/**
 * Legacy footer abbreviation CommonMark renderer.
 *
 * @see \Drupal\omnipedia_content_legacy\EventSubscriber\Markdown\CommonMark\AbbreviationFooterEventSubscriber
 *   Explains the purpose of this renderer.
 */
class AbbreviationFooterRenderer implements BlockRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function render(
    AbstractBlock $block,
    ElementRendererInterface $htmlRenderer,
    bool $inTightList = false
  ) {

    if (!($block instanceof AbbreviationFooter)) {
      throw new \InvalidArgumentException(
        'Incompatible block type: ' . \get_class($block)
      );
    }

    return $block->render();

  }

}
