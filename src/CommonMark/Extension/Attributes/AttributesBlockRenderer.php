<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Extension\Attributes;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;

class AttributesBlockRenderer implements BlockRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function render(
    AbstractBlock $block,
    ElementRendererInterface $htmlRenderer,
    bool $inTightList = false
  ) {

    return '';

  }

}
